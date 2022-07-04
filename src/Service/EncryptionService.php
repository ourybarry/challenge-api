<?php

namespace App\Service;

class EncryptionService{
    static public $DEFAULT_CIPHER = 'AES-128-CBC';
    static public $DEFAULT_KEY_LENGTH = 16;
    static public $CHUNK_SIZE = 1024*1024*8; //8MB


    public function __construct()
    {
    }

    /**
     * Encrypt an entire file by CHUNK_SIZE blocks and remove the original plaintext file
     */
    public function encryptFile($sourcefilePath, $key, $destinationFilePath){
        $passphrase = substr(sha1($key, true), 0, 16);
        $iv = openssl_random_pseudo_bytes(16); //First initialization vector that we will add to beginning of our file
        $output = fopen($destinationFilePath, 'w');
        if($output){ //After successfuly opening our output file we write iv at his beginning 
            fwrite($output, $iv);
            $inputFile = fopen($sourcefilePath, 'rb');
            while(!feof($inputFile)){ 
                $dataToEncrypt = fread($inputFile, EncryptionService::$CHUNK_SIZE);
                $cipherText = openssl_encrypt($dataToEncrypt, EncryptionService::$DEFAULT_CIPHER, $passphrase, OPENSSL_RAW_DATA,$iv);
                 //Next iv is the begining of this current ciphertext block
                $iv = substr($cipherText, 0,16);
                //We write ciphertext to output file
                fwrite($output, $cipherText);
            }
	    fclose($inputFile);
	    
	    fclose($output);
        }
        //We delete the original plaintextfile after encryption
	    unlink($sourcefilePath);
    }

    public function decryptChunk($chunkData, $key, $iv){
        $passphrase = substr(sha1($key, true), 0, EncryptionService::$DEFAULT_KEY_LENGTH);
       
        return openssl_decrypt($chunkData, EncryptionService::$DEFAULT_CIPHER, $passphrase, OPENSSL_RAW_DATA, $iv);
    }
}
