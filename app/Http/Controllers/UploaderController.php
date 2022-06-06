<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Pion\Laravel\ChunkUpload\Exceptions\UploadFailedException;

use Storage;
use Illuminate\Http\UploadedFile;
use Pion\Laravel\ChunkUpload\Exceptions\UploadMissingFileException;
use Pion\Laravel\ChunkUpload\Handler\AbstractHandler;
use Pion\Laravel\ChunkUpload\Handler\HandlerFactory;
use Pion\Laravel\ChunkUpload\Receiver\FileReceiver;
use App\Models\Tasks;

class UploaderController extends Controller
{
    public $uploadPath = '/storage/app/public/uploads/';
}
