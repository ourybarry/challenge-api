<?php

namespace App\Service;

use App\Entity\StorageItem;
use App\Repository\StorageItemRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\AppException\AppException;
use Symfony\Component\HttpFoundation\Response;
class StorageItemService
{

    private StorageItemRepository $_storageItemRepository;
    private EntityManagerInterface $_entityManager;
    private UserRepository $_userRepository;

    public function __construct(StorageItemRepository $storageItemRepository, EntityManagerInterface $entityManager, UserRepository $userRepository)
    {
        $this->_storageItemRepository = $storageItemRepository;
        $this->_entityManager = $entityManager;
        $this->_userRepository = $userRepository;
    }

    public function createDirectory(array $directoryData, $userId)
    {
        //Same pattern we validate data then persist in database

        //validation

        //database persist

        $directoryName = $directoryData['name'];
        $directoryStatus = 'available';
        $directorySize = 0;
        $directoryParent = $this->_storageItemRepository->findOneBy(['id' => $directoryData['parent']]); //we retrieve parent direcotry
        $directoryId = substr(bin2hex(openssl_random_pseudo_bytes(16)), 0, 16);

        $directoryOwner = $this->_userRepository->findOneBy(['id' =>$userId]);

        $directory = new StorageItem();
        $directory->setItemType('directory')
            ->setName($directoryName)
            ->setMimeType(null)
            ->setStatus($directoryStatus)
            ->setSize($directorySize)
	    ->setOwner($directoryOwner)
            ->setCreatedAt(new \DateTimeImmutable())
            ->setUpdatedAt(new \DateTimeImmutable()) //Item already exists so we just need to update his updatedAt date
            ->setParentDirectory($directoryParent)
            ->setFileId($directoryId);
        $this->_entityManager->persist($directory);
        $this->_entityManager->flush();
        return $directory;
    }

    public function getDirectoryContent($directory, $userId)
    {
        $result = $this->_storageItemRepository->findBy(['parentDirectory' => $directory, 'owner' => $userId, 'status'=>['available', 'pending']]);
        return $result;
    }

    public function getItemDetails(StorageItem $item)
    {
        return $item;
    }

    public function renameItem(StorageItem $item, string $newName)
    {
        $item->setName($newName);
        $item->setUpdatedAt(new \DateTimeImmutable());
        $this->_entityManager->flush();
        return $item;
    }

    //We are going to do soft deletion that way user can always retrieve his data
    public function deleteItem(StorageItem $item, $userId)
    {
	    //Only items owners are allowed to delete items
	 $this->throwExceptionIfNotOwner($item, $userId);
        $item->setStatus('deleted');
        $item->setUpdatedAt(new \DateTimeImmutable());
        $this->_entityManager->flush();
    }


    /**
     * With soft deletion, user can still see his deleted data in his bin, with fake hard delete, 
     * he can also delete items from bin, however his data will remain saved on our server
     */
    public function fakeHardDelete(StorageItem $item, $userId)
    {
	    $this->throwExceptionIfNotOwner($item, $userId);
        $item->setDeleted(true);
        $item->setUpdatedAt(new \DateTimeImmutable());
        $this->_entityManager->flush();
    }

    public function recoverItem(StorageItem $item, $userId)
    {
	    $this->throwExceptionIfNotOwner($item, $userId);
        $item->setStatus('available');
        $item->setUpdatedAt(new \DateTimeImmutable());
        $this->_entityManager->flush();
    }

    public function getItemPath($itemId)
    {
        
        if ($itemId == null) {
            return []; //root dir have empty path
        }

        $item = $this->_storageItemRepository->findOneBy(['id'=> $itemId]);
        $path = [$item];
        while ($item->getParentDirectory() != null) {
            array_push($path, $item->getParentDirectory()); 
            $item = $item->getParentDirectory();
        }
        return array_reverse($path);
    }
    public function loadTrashContent($userId){
    	$result = $this->_storageItemRepository->findBy(['owner'=> $userId, 'status'=> 'deleted', 'deleted'=>0 ]);
	return $result;
    }
    /**
     * Helper method that throws exception if a user try to perform unauthorized operation on another user's property
     */
    public function throwExceptionIfNotOwner(StorageItem $item, $userId){
    
	    if($item->getOwner()->getId() != $userId){
	    	throw new AppException(['You are not allowed to perform this operation '.$userId], Response::HTTP_UNAUTHORIZED);
	    }
    }
}
