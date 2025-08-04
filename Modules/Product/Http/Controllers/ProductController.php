<?php

namespace Modules\Product\Http\Controllers;

use Modules\Product\DataTables\ProductDataTable;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Modules\Product\Entities\Product;
use Modules\Product\Http\Requests\StoreProductRequest;
use Modules\Product\Http\Requests\UpdateProductRequest;
use Modules\Upload\Entities\Upload;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{

    public function index(ProductDataTable $dataTable) {
        abort_if(Gate::denies('access_products'), 403);

        return $dataTable->render('product::products.index');
    }


    public function create() {
        abort_if(Gate::denies('create_products'), 403);

        return view('product::products.create');
    }


    public function store(StoreProductRequest $request) {


        $product = Product::create($request->except('document'));


        if ($request->has('document')) {
            foreach ($request->input('document', []) as $file) {
                $tempFilePath = storage_path('app/public/temp/dropzone/' . $file);

                if (file_exists($tempFilePath)) {
                    $newFileName = Str::slug($product->product_name) . '_' . time() . '.' . pathinfo($file, PATHINFO_EXTENSION);

                    $product->addMedia($tempFilePath)
                            ->usingFileName($newFileName)
                            ->toMediaCollection('images');
                }
            }
        }

        // if ($request->has('document')) {
        //     foreach ($request->input('document', []) as $file) {
        //         $product->addMedia(Storage::path('temp/dropzone/' . $file))->toMediaCollection('images');
        //     }
        // }

        toast('Product Created!', 'success');

        return redirect()->route('products.index');
    }


    public function show(Product $product) {
        abort_if(Gate::denies('show_products'), 403);

        return view('product::products.show', compact('product'));
    }


    public function edit(Product $product) {
        abort_if(Gate::denies('edit_products'), 403);

        return view('product::products.edit', compact('product'));
    }


    public function update(UpdateProductRequest $request, Product $product) {
        $product->update($request->except('document'));

        if ($request->has('document')) {
            // Logika untuk menghapus gambar lama yang tidak ada di request baru
            if (count($product->getMedia('images')) > 0) {
                foreach ($product->getMedia('images') as $media) {
                    if (!in_array($media->file_name, $request->input('document', []))) {
                        $media->delete();
                    }
                }
            }

            // Logika untuk menambah gambar baru
            $media = $product->getMedia('images')->pluck('file_name')->toArray();
            foreach ($request->input('document', []) as $file) {
                // Hanya tambahkan file jika belum ada di koleksi media
                if (count($media) === 0 || !in_array($file, $media)) {
                    $tempFilePath = storage_path('app/public/temp/dropzone/' . $file);

                    if (file_exists($tempFilePath)) {
                        $newFileName = Str::slug($product->product_name) . '_' . time() . '.' . pathinfo($file, PATHINFO_EXTENSION);

                        $product->addMedia($tempFilePath)
                                ->usingFileName($newFileName)
                                ->toMediaCollection('images');
                    }
                }
            }
        }

        toast('Product Updated!', 'info');

        return redirect()->route('products.index');
    }


    public function destroy(Product $product) {
        abort_if(Gate::denies('delete_products'), 403);

        $product->delete();

        toast('Product Deleted!', 'warning');

        return redirect()->route('products.index');
    }
}
