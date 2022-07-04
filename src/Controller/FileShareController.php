<?php

namespace App\Controller;

use App\Entity\FileShare;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class FileShareController extends AbstractController
{
    /**
     * Share file with another user
     */
    #[Route('/share', name: 'app_create_file_share', methods: ['POST'])]
    public function createFileShare(Request $request): Response
    {
        return $this->json(['create share']);
    }

    /**
     * Retrieve files shared with a given user (Which files were shared with user John ?)
     */
    #[Route('/share/{userId}', name: 'app_user_shared_with', methods: ['GET'])]
    public function getFilesSharedWithUser($userId){

    }

    /**
     * Retrieve Users with whom a file has been shared (With which user file x has been shared ?)
     */
    #[Route('/share/{fileId}', name: 'app_file_shared_with', methods: ['GET'])]
    public function getUsersSharingFile($fileId){

    }

    /**
     * Delete single file share
     */
    #[Route('/share/{id}/delete', name: 'app_delete_user_share', methods: ['DELETE'])]
    public function deleteFileShare(FileShare $fileShare){
        
    }
}
