<?php

namespace App\Http\Controllers;

use App\Models\File;
use http\Env\Response;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Pion\Laravel\ChunkUpload\Handler\HandlerFactory;
use Pion\Laravel\ChunkUpload\Receiver\FileReceiver;
use Spatie\Crypto\Rsa\KeyPair;
use Spatie\Crypto\Rsa\PrivateKey;
use Spatie\Crypto\Rsa\PublicKey;

class FileController extends Controller
{


    public function index()
    {
        return view('file_upload');
    }

    /**
     * @throws \Pion\Laravel\ChunkUpload\Exceptions\UploadFailedException
     */
    public function store(Request $request)
    {
        ini_set('max_execution_time', 90);
        $receiver = new FileReceiver('file', $request, HandlerFactory::classFromRequest($request));

        if (!$receiver->isUploaded()) {
            // file not uploaded
        }

        $fileReceived = $receiver->receive(); // receive file
        if ($fileReceived->isFinished()) { // file uploading is complete / all chunks are uploaded

            $file = $fileReceived->getFile(); // get file
            $extension = $file->getClientOriginalExtension();
            $fileName = str_replace('.'.$extension, '', $file->getClientOriginalName()); //file name without extenstion
            $fileName .=  '.' . $extension; // a unique file name

            $disk = Storage::disk(config('filesystems.default'));
            $path = $disk->putFileAs('tmp', $file, $fileName);

            // delete chunked file
            unlink($file->getPathname());
            return [
                'path' => asset('storage/' . $path),
                'filename' => $fileName
            ];

        }


        // otherwise return percentage information
        $handler = $fileReceived->handler();
        return [
            'done' => $handler->getPercentageDone(),
            'status' => true
        ];

    }

    public function extract($filename)
    {
        ini_set('max_execution_time', 600);
        $localDisk = Storage::disk('local');
        $webdavDisk = Storage::disk('webdav');

        $this->debugWithDate('Inizio la decompressione');

        /**
         * Tolgo la compressione al file gz
         */
        $compressedTarFilePath = $localDisk->path('tmp/'.$filename);
        $compressedTarFileDescriptor = gzopen($compressedTarFilePath, 'rb');
        $tarFileDescriptor = fopen($localDisk->path('tmp/').'decompressed.tar', 'wb');

        stream_copy_to_stream($compressedTarFileDescriptor, $tarFileDescriptor);

        fclose($tarFileDescriptor);
        gzclose($compressedTarFileDescriptor);

        $this->debugWithDate('Decompressione terminata. Inizio l\'estrazione');

        /**
         * Estraggo il tar decompresso in una cartella temporanea
         */
        $localDisk->deleteDirectory('tmp/KnowledgeBase');
        $tarFile = new \PharData($localDisk->path('tmp/decompressed.tar'));
        $tarFile->extractTo( $localDisk->path('tmp/'));

        $this->debugWithDate('Finita l\'estrazione inizio il caricamento in webdav');

        /*
         * Per ogni cartella, vado a caricare i file con webdav
         */
        $folders  = $localDisk->directories('tmp/KnowledgeBase/');
        foreach($folders as $folder){
            $files = $localDisk->files($folder);
            $explode = explode('/', $folder);
            $dir = $explode[2];

            foreach ($files as $file) {
                $savePath = 'files/' . $dir . '/' . basename($file);
                $fileExistsInWebdav = $webdavDisk->exists($savePath);
                if($fileExistsInWebdav) {
                    $webdavDisk->delete($savePath);
                }

                $webdavDisk->writeStream($savePath, $localDisk->readStream($file));
            }

        }

        /**
         * Elimino i file non più necessari
         */
        $this->debugWithDate('Terminato il caricamento, elimino i file non più necessari');

        /*
         * unset è necessaria perchè phardata non chiude il descrittore fino a che non viene terminato lo script, in questo modo lo chiudo brutalmente
         */
        unset($tarFile);
      //  $localDisk->deleteDirectory('tmp');
    }


    private function debugWithDate($text) {
        dump(date('H:i:s').': '.$text);
    }

    public function certificate()
    {
        $source = 'signed';
        echo "Source: $source";
        $fp=fopen(Storage::path('tmp/public.crt'),"r");
        $pub_key=fread($fp,8192);

        $publicKey = "-----BEGIN PUBLIC KEY-----\n" . wordwrap($pub_key, 64, "\n", true) . "\n-----END PUBLIC KEY-----";

        $str = "str to be encrypted";

        $opensslPublicEncrypt = openssl_public_encrypt($str, $encrypted, $publicKey);

        fclose($fp);
        exit();
        openssl_get_publickey($pub_key);
        /*
        * NOTE:  Here you use the $pub_key value (converted, I guess)
        */
        openssl_public_encrypt($source,$crypttext,$pub_key);
        echo "String crypted: $crypttext";

        $fp=fopen("/path/to/private.key","r");
        $priv_key=fread($fp,8192);
        fclose($fp);
        $passphrase ='test';
// $passphrase is required if your key is encoded (suggested)
        $res = openssl_get_privatekey($priv_key,$passphrase);
        /*
        * NOTE:  Here you use the returned resource value
        */
        openssl_private_decrypt($crypttext,$newsource,$res);
        echo "String decrypt : $newsource";
    }

    public function encrypt() {

        $publicKey = Storage::path('tmp/id_rsa');
        $key = wordwrap($publicKey, 65, "\n", true);
        $plaintext = "String to encrypt";

        $pubKey = openssl_pkey_get_public($publicKey);

        openssl_public_encrypt($plaintext, $encrypted, $pubKey);

        echo $encrypted;   //encrypted string

    }
}

