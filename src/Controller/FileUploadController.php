<?php

namespace App\Controller;

use App\AppException\AppException;
use App\Entity\StorageItem;
use App\Service\FileDownloadService;
use App\Service\FileUploadService;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Our controller's pattern is pretty basic, We pass the request data to a service
 * That service will handle the task of the called controller and raise exception if error occured
 * When exceptions are raised, we catch them and return an error response to 
 * our client otherwise we sent him a success response
 */

#[Route('/api/v1')]
class FileUploadController extends AbstractController
{


    private FileUploadService $_fileUploadService;
    private FileDownloadService $_fileDownloadService;
    private NormalizerInterface $_normalizer;

    public function __construct(FileUploadService $fileUploadService, NormalizerInterface $normalizer, FileDownloadService $fileDownloadService)
    {
        $this->_fileUploadService = $fileUploadService;
        $this->_fileDownloadService = $fileDownloadService;
        $this->_normalizer = $normalizer;
    }

    /**
     * Here we initialize the multipart upload, we are not uploading the actual file here,
     * we are just adding his metadata in our database then we return a key to users that they
     * can use to upload file parts
     */
    #[Route('/multipart_upload/initialize', name: 'app_initialize_multipart_upload', methods: ['POST'])]
    public function initializeMultipartUpload(Request $request): Response
    {
        try {
            $data = $request->toArray();
            $authenticatedUser = $request->headers->get('user');
            $result = $this->_fileUploadService->initializeMultipartUpload($data, $authenticatedUser);
            // We normalize result before sending it to client
            $normalizedResult = $this->_normalizer->normalize($result, null, [
                AbstractNormalizer::ATTRIBUTES => ['id', 'name', 'mimeType', 'parentDirectory'=>['id'], 'size', 'status', 'createdAt', 'updatedAt', 'fileId', 'owner' => ['email']]
            ]);
            return $this->json($normalizedResult);
        } catch (AppException $exception) {
            return $this->json(['error' => $exception->getErrorMessage()], $exception->getStatusCode());
        }
    }

    /**
     * After the MultipartUpload has been initialized, the client uses the below route 
     * to send file chunks
     */
    #[Route('/multipart_upload/send_part/{fileId}-{chunkNumber}', name: 'app_send_multipart_upload_part', methods: ['POST'])]
    public function sendMultipartUploadPart(StorageItem $item, Request $request, $chunkNumber)
    {

            $authenticatedUser = $request->headers->get('user');
        // if(!$chunkNumber) return $this->json(['error'=> $chunkNumber], Response::HTTP_BAD_REQUEST);
        //Upload Part
        try {
            $uploadResult = $this->_fileUploadService->uploadMultipartUploadPart($item, $request->files->get('chunkData'), $authenticatedUser);
            return $this->json(['message' => 'Successfuly writen ' . $uploadResult . ' bytes']);
        } catch (AppException $exception) {
            //If write failed we return an error response
            return $this->json(['error' => $exception->getErrorMessage()], $exception->getStatusCode());
        }
    }

    /**
     * After the client sent all the parts, we complete the upload and update item state 
     * in our database. We can do a lot of stuff in this controller, like verifying if the uploaded file size
     * match the one our client declared, and more, but for now i will just set the StorageItem status to available
     */
    #[Route('/multipart_upload/complete_upload/{fileId}', name: 'app_complete_multipart_upload')]
    public function completeMultipartUpload(StorageItem $storageItem)
    {
        try {
            $uploadedItem = $this->_fileUploadService->completeMultipartUpload($storageItem);
            $normalizedUploadedItem = $this->_normalizer->normalize($uploadedItem, null, [
                AbstractNormalizer::ATTRIBUTES => ['id', 'name', 'itemType', 'mimeType', 'parentDirectory' => ['id', 'name'], 'createdAt', 'updatedAt', 'fileId', 'owner' => ['id', 'email'], 'status', 'size']
            ]);
            return $this->json($normalizedUploadedItem);
        } catch (AppException $exception) {
            return $this->json(['error' => $exception->getErrorMessage()], $exception->getStatusCode());
        }
    }


    #[Route('/multipart_download/download/{fileId}', name: 'app_download_file')]
    public function downloadFile(StorageItem $storageItem, Request $request)
    {
            $authenticatedUser = $request->headers->get('user');
	    try{  
		    $download = $this->_fileDownloadService->downloadFile($storageItem, $authenticatedUser);
		    return $download;
	    }catch(AppException $exception){
	    	return $this->json(['error'=>$exception->getErrorMessage()], $exception->getStatusCode());
	    }
    }
}
