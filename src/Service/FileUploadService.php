<?php

namespace App\Service;

use App\AppException\AppException;
use App\Entity\StorageItem;
use App\Repository\StorageItemRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\HttpFoundation\Response;

class FileUploadService
{

    private StorageItemRepository $_storageItemRepository;
    private EntityManager $_entityManager;
    private RequestBodyValidatorService $_requestBodyValidatorService;
    private UserRepository $_userRepository;
    private EncryptionService $_encryptionService;
    private string $_uploadRoot;



    public function __construct(StorageItemRepository $storageItemRepository, EntityManagerInterface $entityManager, RequestBodyValidatorService $requestBodyValidatorService, UserRepository $userRepository, EncryptionService $encryptionService, string $upload_root)
    {
        $this->_storageItemRepository = $storageItemRepository;
        $this->_entityManager = $entityManager;
        $this->_requestBodyValidatorService = $requestBodyValidatorService;
        $this->_userRepository = $userRepository;
        $this->_encryptionService = $encryptionService;
        $this->_uploadRoot = $upload_root;
    }

    public function initializeMultipartUpload(array $fileMetadata, $userId)
    {
        
        $user = $this->_userRepository->findOneBy(['id' => $userId]);
        //We validate the request body
        $requiredAttributes = ['fileName', 'mimeType', 'fileSize', 'parentDirectory'];
        $validationResult = $this->_requestBodyValidatorService->bodyIsValid($requiredAttributes, $fileMetadata);
        //If we have validation errors, raise exception
        if (count($validationResult) > 0) throw new AppException($validationResult, Response::HTTP_BAD_REQUEST);
        //We check if a file with the same name exists at destination
        $fileName = $fileMetadata['fileName'];
        $item = $this->_storageItemRepository->findOneBy([
            'name' => $fileName,
	    'owner' => $userId,
	    'itemType'=> 'file',
	    'status'=>['available','pending'],
            'parentDirectory' => $fileMetadata['parentDirectory']
        ]);

        //If we find one occurence, we raise an exception with http conflict status so the controller can return it to the client
        //to let him choose wether if he would like to replace the existing file
        if ($item != null) {
            $replace = $fileMetadata['replace'] ?? false;
            //If replace is false we raise exception
            if (!$replace) {
                throw new AppException(['File already exists, you may want to retry request with replace flag set to true'], Response::HTTP_CONFLICT);
            }else{
                //We delete the old file
            }
        } else {
            //If item does not exists
            $item = new StorageItem();
            $item->setCreatedAt(new \DateTimeImmutable())
                ->setUpdatedAt(new \DateTimeImmutable())
                ->setFileId(substr(bin2hex(openssl_random_pseudo_bytes(32)), 0, 16)); //Maybe we should check for database occurences ??
        }
        //We persist our new StorageItem in database

        $mimeType = $fileMetadata['mimeType'];
        $itemStatus = 'pending';
        $itemSize = $fileMetadata['fileSize'];
        $itemParentDirectory = $fileMetadata['parentDirectory'] ? $this->_storageItemRepository->findOneBy(['id'=> $fileMetadata['parentDirectory']]): null;
        // $itemFileId = openssl_random_pseudo_bytes();
        
        $item->setItemType('file')
            ->setName($fileName)
            ->setMimeType($mimeType)
            ->setStatus($itemStatus)
	    ->setSize($itemSize)
    	    ->setDeleted(0)
            ->setOwner($user)
            ->setUpdatedAt(new \DateTimeImmutable()) //Item already exists so we just need to update his updatedAt date
            ->setParentDirectory($itemParentDirectory)
            ;

        $this->_entityManager->persist($item);
        $this->_entityManager->flush();
        //We return the new StorageItem object to our controller
        return $item;
    }

    public function uploadMultipartUploadPart(StorageItem $storageItem, $dataToWrite, $userId)
    {
	    $parentDirectory = $this->_uploadRoot.'/'.$userId; // 'ROOT_UPLOAD_DIR/USER_ID/
	//We create parent directory if it doesn't exists
        if(!is_dir($parentDirectory)){ 
	     mkdir($parentDirectory);	
	}
        $fullTargetPath = $parentDirectory.'/'.$storageItem->getFileId();
        $write = file_put_contents($fullTargetPath, file_get_contents($dataToWrite), FILE_APPEND);
        //If write operation failed we raise exception
        if(!$write) throw new AppException(['Failed to write to target file'], Response::HTTP_INTERNAL_SERVER_ERROR);
        return $write;
    }

    public function completeMultipartUpload(StorageItem $storageItem)
    {
	//To finalize upload we will encrypt the file
	$filePath = $this->_uploadRoot.'/'.$storageItem->getOwner()->getId().'/'.$storageItem->getFileId();
	$outputFilePath = $filePath.'.enc';
	$key = $storageItem->getOwner()->getUuid();
	$this->_encryptionService->encryptFile($filePath, $key, $outputFilePath);
        $storageItem->setStatus('available')
                    ->setUpdatedAt(new \DateTimeImmutable());
        $this->_entityManager->flush();
        return $storageItem;
    }
}
