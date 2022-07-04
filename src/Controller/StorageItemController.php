<?php

namespace App\Controller;

use App\AppException\AppException;
use App\Entity\StorageItem;
use App\Service\StorageItemService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;


#[Route('/api/v1')]
class StorageItemController extends AbstractController
{
    private StorageItemService $_storageItemService;
    private NormalizerInterface $_normalizer;

    public function __construct(StorageItemService $storageItemService, NormalizerInterface $normalizer)
    {
        $this->_storageItemService = $storageItemService;
        $this->_normalizer = $normalizer;
    }

    /**
     * Route used for creating directories
     */
    #[Route('/storage', name: 'app_create_storage_item', methods: ['POST'])]
    public function createDirectory(Request $request){
        try {
		$directoryData = $request->toArray();
		$authenticatedUser = $request->headers->get('user');
            $directory = $this->_storageItemService->createDirectory($directoryData, $authenticatedUser);
            $normalizedDirectoryItem = $this->_normalizer->normalize($directory, null, [
                AbstractNormalizer::ATTRIBUTES => ['id', 'name', 'fileId', 'createdAt', 'updatedAt', 'owner'=>['email'], 'status']
            ]);
            return $this->json(['directory'=> $normalizedDirectoryItem]);
        } catch (AppException $exception) {
            return $this->json(['error'=>$exception->getErrorMessage()], $exception->getStatusCode());
        }
    }
    
    #[Route('/storage/content', name: 'app_storage_item_content', methods: ['GET'])]
    public function getStorageItemContent(Request $request): JsonResponse
    {
        try {
		$dirId = $request->query->get('directory');
		
		$authenticatedUser = $request->headers->get('user');
            $content = $this->_storageItemService->getDirectoryContent($dirId, $authenticatedUser);
            $normalizedDirectoryItem = $this->_normalizer->normalize($content, null, [
                AbstractNormalizer::ATTRIBUTES => ['id', 'name', 'fileId', 'itemType', 'mimeType','size','createdAt', 'updatedAt', 'owner'=>['email'], 'status', 'parentDirectory'=>['id'] ]
            ]);
            return $this->json([$normalizedDirectoryItem]);
        } catch (AppException $exception) {
            return $this->json(['error'=>$exception->getErrorMessage()], $exception->getStatusCode());
        }
    }
    #[Route('/storage/tree', name: 'app_storage_item_path')]
    public function getStorageItemPath(Request $request){
        $id = $request->query->get('id');
        $result = $this->_storageItemService->getItemPath($id);
        $normalizedData = $this->_normalizer->normalize($result, null, [
            AbstractNormalizer::ATTRIBUTES => ['id', 'name']
        ]);
        return $this->json([$normalizedData]);
    }
    #[Route('/storage/{id}/details', name: 'app_storage_item_details', methods: ['GET'])]
    public function getStorageItemDetails(StorageItem $storageItem){
        try {
            $item =  $this->_storageItemService->getItemDetails($storageItem);
            $normalizedDirectoryItem = $this->_normalizer->normalize($item, null, [
                AbstractNormalizer::ATTRIBUTES => ['id', 'name', 'fileId', 'size', 'itemType','createdAt', 'updatedAt', 'owner'=>['email'], ]
            ]);
            return $this->json(['item'=> $normalizedDirectoryItem]);
        } catch (AppException $exception) {
            return $this->json(['error'=>$exception->getErrorMessage()], $exception->getStatusCode());
        }
    }

    /**
     * We will use the below route to rename our items
     */
    #[Route('/storage/{id}', name: 'app_update_storage_item', methods: ['PATCH'])]
    public function updateStorageItem(StorageItem $storageItem){

    }


    #[Route('/trash', name: 'app_user_trash', methods: ['GET'])]
    public function getDeletedFiles(Request $request){
    	
	    try {
		    $authenticatedUser = $request->headers->get('user');
            $item =  $this->_storageItemService->loadTrashContent($authenticatedUser);
            $normalizedDirectoryItem = $this->_normalizer->normalize($item, null, [
                AbstractNormalizer::ATTRIBUTES => ['id', 'name', 'fileId', 'size', 'itemType','createdAt', 'updatedAt', 'owner'=>['email'], ]
            ]);
            return $this->json([$normalizedDirectoryItem]);
        } catch (AppException $exception) {
            return $this->json(['error'=>$exception->getErrorMessage()], $exception->getStatusCode());
        }
    }

    #[Route('/storage/{id}/delete', name: 'app_delete_storage_item')]
    public function deleteStorageItem(StorageItem $storageItem, Request $request){
	    $authenticatedUser = $request->headers->get('user');
	    try{
	    	$this->_storageItemService->deleteItem($storageItem, $authenticatedUser);
		return $this->json(['success'=>'Item '.$storageItem->getId().' deleted']);
	    }catch(AppException $exception){
	    	return $this->json(['error'=>$exception->getErrorMessage()], $exception->getStatusCode());
	    }
    }

    #[Route('/storage/{id}/wipe', name: 'app_delete_storage_item_permanent')]
    public function wipeStorageItem(StorageItem $storageItem, Request $request){
	    $authenticatedUser = $request->headers->get('user');
	    try{
	    	$this->_storageItemService->fakeHardDelete($storageItem, $authenticatedUser);
		return $this->json(['success'=>'Item '.$storageItem->getId().' deleted']);
	    }catch(AppException $exception){
	    	return $this->json(['error'=>$exception->getErrorMessage()], $exception->getStatusCode());
	    }
    }
    
    #[Route('/storage/{id}/recover', name: 'app_recover_storage_item')]
    public function recoverStorageItem(StorageItem $storageItem, Request $request){
	    $authenticatedUser = $request->headers->get('user');
	    try{
	    	$this->_storageItemService->recoverItem($storageItem, $authenticatedUser);
		return $this->json(['success'=>'Item '.$storageItem->getId().' recovered']);
	    }catch(AppException $exception){
	    	return $this->json(['error'=>$exception->getErrorMessage()], $exception->getStatusCode());
	    }
    }
}
