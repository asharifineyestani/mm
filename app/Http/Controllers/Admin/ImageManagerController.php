<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Session;
use Zebra_Pagination;

class ImageManagerController extends Controller
{
    public function index(Request $request)
    {
        if (is_null($request->page)){
            return redirect()->route('admin.imageManager.index', ['page' => 1]);
        }

        $dirname = public_path('uploads/media');

        $files = scandir($dirname);
        $images = array_filter($files, function ($image){
            return  $image != '.' && $image != '..' && strpos($image, 'wb-body-') !== false;
        });


        $records_per_page = 10;
        $pagination = new Zebra_Pagination();
        $pagination->records(count($images));
        $pagination->records_per_page($records_per_page);

        $images = array_slice(
            $images,
            (($pagination->get_page() - 1) * $records_per_page),
            $records_per_page
        );

        return view('admin.ImageManager.index', [
            'images' => $images,
            'pagination' => $pagination
        ]);
    }

    public function destroy($image)
    {
        if (is_file(public_path('uploads/media/' . $image))){
            unlink(public_path('uploads/media/' . $image));
            if (is_file(public_path('uploads/thumbnail/' . $image))){
                unlink(public_path('uploads/thumbnail/' . $image));
            }
            Session::flash('alert-info', 'success,' . __('mm.public.selected_image_deleted'));
        }

        return redirect()->back();
    }

    public function deleteSelectedImages($images)
    {
        $images = (array)explode(',', $images);

        foreach ($images as $image){
            if (is_file(public_path('uploads/media/' . $image))){
                unlink(public_path('uploads/media/' . $image));
                if (is_file(public_path('uploads/thumbnail/' . $image))){
                    unlink(public_path('uploads/thumbnail/' . $image));
                }
                Session::flash('alert-info', 'success,' . __('mm.public.selected_images_deleted'));
            }
        }

        return redirect()->back();
    }
    public function deleteAllImages(){
        $dirname = public_path('uploads/media');
        foreach( glob("$dirname/*") as $file ) {
            if ($file != '.' && $file != '..') {
                if (strpos($file, 'wb-body-') !== false) {
                    unlink($file);
                }
            }
        }
        return redirect()->back();
    }
}
