<?php

namespace App\Service;

use App\AppException\AppException;
use App\Entity\StorageItem;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Repository\FileShareRepository;


use Symfony\Component\HttpFoundation\Response;

class FileDownloadService{


    private EncryptionService $_encryptionService;
    private FileShareRepository $_fileShareRepository;

    //Uploads root foolder path
    private string $_uploadRoot;

    public function __construct(EncryptionService $encryptionService, FileShareRepository $fileShareRepository, string $upload_root)
    {
	    $this->_encryptionService = $encryptionService;
	    $this->_fileShareRepository = $fileShareRepository;
        $this->_uploadRoot = $upload_root;
    }

    //Generate a streaming file response and handle it to controller that will return it to client
    public function downloadFile(StorageItem $storageItem, $userId){

	    //Check if this user is allowed to download the file before sending it
	//If user trying to download the file is not the file owner, check if the owner shared the file with him, if neither, refuse download
	//$userId is the id of the user trying to download the file
	    if($userId != $storageItem->getOwner()->getId()){
	    	//Let's check if the file has been shared with him
		    $fileShare = $this->_fileShareRepository->findOneBy(['sharedWith'=> $userId]);
		    if(!$fileShare){
		    	//File has not been shared with this user
			throw new AppException(['You are not allowed to access this file'], Response::HTTP_UNAUTHORIZED);
		    }
	    }

        $fileName = $storageItem->getName();

        $response = new StreamedResponse();

        //Setting up response headers

        $response->headers->set('Content-Type', $storageItem->getMimeType());
        $response->headers->set('Content-Length', $storageItem->getSize());
        $response->headers->set('Content-Disposition', 'attachment; filename="'.$fileName.'"');
        $response->setCallback(function () use ($storageItem) {
            $filePath = $this->_uploadRoot.'/'.$storageItem->getOwner()->getId().'/'.$storageItem->getFileId().'.enc';
            $fileInput = fopen($filePath, 'rb');
            $iv = fread($fileInput, 16); //First iv is at beginning of file
            while(!feof($fileInput)){
                
                
		
                $data = fread($fileInput, EncryptionService::$CHUNK_SIZE+16); 
                
                //We decrypt data before sending it to client
                $key = $storageItem->getOwner()->getUuid();
                $decryptedData = $this->_encryptionService->decryptChunk($data, $key, $iv);
                
              	echo($decryptedData);
                //The next iv is at the beginning of the current encrypted block
                $iv = substr($data, 0,16);
                ob_flush();
                flush();
	    }
            fclose($fileInput);
        });
        return $response;
    }
}
