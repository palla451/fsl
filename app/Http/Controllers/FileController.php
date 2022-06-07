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
        $path = Storage::disk('local')->path('tmp/'.$filename);

        $file = gzopen($path, 'rb');

        $out_file = fopen(Storage::path('tmp/').'test.tar', 'wb');

        while (!gzeof($file)) {
            // Read buffer-size bytes
            // Both fwrite and gzread and binary-safe
            fwrite($out_file, gzread($file, 4096));
        }

        fclose($out_file);
        gzclose($file);

        $phar = new \PharData(Storage::disk('local')->path('tmp/test.tar'));
        $phar->extractTo( Storage::disk('local')->path('tmp/'));

        $folders  = Storage::directories('tmp/fsl/');



        foreach($folders as $folder){
           $files = Storage::disk('local')->files($folder);
           $explode = explode('/', $folder);
           $dir = $explode[2];
            dump($folder, $files);
            foreach ($files as $file) {

                Storage::disk('webdav')->writeStream('files/'.$dir.'/'.basename($file), Storage::disk('local')->readStream($file));

                // If you no longer need the originals
                //Storage::disk($from)->delete($file);
            }

      //      Storage::disk('public')->deleteDirectory($directory);
        }



//        foreach($folders as $folder){
//            // spedire i file sulla webdav in base al nome delle directories
//
//            $files = Storage::files($folder);
//
//            foreach ($files as $file){
//
//                $full_path_source = Storage::disk('public')->getDriver()->getAdapter()->applyPathPrefix($file);
//
//                $full_path_dest = Storage::disk('webdav')->getDriver()->getAdapter()->applyPathPrefix('files');
//
//               // dump($full_path_source . ' - ' . $full_path_dest);
//
//                // make destination folder
//                if (!\Illuminate\Support\Facades\File::exists(dirname($full_path_dest))) {
//                    \Illuminate\Support\Facades\File::makeDirectory(dirname($full_path_dest), null, true);
//                }
//
//                \Illuminate\Support\Facades\File::move($full_path_source, $full_path_dest);
//            }
//
//
//            dump($files);
//        }

    }
}


//        $file = $request->file('file');
//        dd($file);
//        exit();
//        $decompressed = gzdecode($request->getContent());
//        dd($decompressed);
//        $file = $request->file('file')->getClientOriginalName();
//
//        $request->file('file')->storeAs('gyala',$file,'webdav');
//
//        return response()->json('success');

