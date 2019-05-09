<?php

namespace App\Http\Controllers;


use File;
use Image;
use App\Category;
use App\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::with('category')->orderBy('created_at', 'DESC')->paginate(10);
        return view('products.index', compact('products'));
    }

//tambah produk
    public function create()
    {
        $categories = Category::orderBy('name', 'ASC')->get();
        return view('products.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'code' => 'required|string|max:10|unique:products',
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:100',
            'stock' => 'required|integer',
            'price' => 'required|integer',
            'category_id' => 'required|exists:categories,id',
            'photo' => 'nullable|image|mimes:jpg,png,jpeg'
        ]);

        try{
            //default $photo=null
            $photo = null;
            //jika ada file foto yg dikirim
            if ($request->hasFile('photo'))
            {
                //maka  jalankan method saveFile()
                $photo = $this->saveFile($request->name, $request->file('photo'));
            }

            //simpan data ke tabel products
            $product = Product::create([
                'code' => $request->code,
                'name' => $request->name,
                'description' => $request->description,
                'stock' => $request->stock,
                'price' => $request->price,
                'category_id' => $request->category_id,
                'photo' => $photo
            ]);
            //kalo berhasil direct ke produk.index
            return redirect(route('produk.index'))
                ->with(['success' => '<strong>' . $product->name . '</strong> Ditambahkan']);
        }catch (\Exception $e) {
            //kalo gagal, kembali kehalman sebelumnya dgn notif error
            return redirect()->back()
                ->with(['error' => $e->getMessage()]);
        }
    }

    private function saveFile($name, $photo)
    {
        ////set nama file adalah gabungan antara nama dan time(). ekstensi foto tetap di pertahankan
        $images = str_slug($name) . time() . '.' . $photo->getClientOriginalExtension();

        //set path untuk menyimpan gambar
        $path = public_path('uploads/product');

        //cek jika uploads/product bukan direktori/folder
        if (!File::isDirectory($path))
        {
            //maka folder tersebut dibuat
            File::makeDirectory($path, 0777, true, true);
        }
        //simpan gambar yg diupload ke folder upload/produk
        Image::make($photo)->save($path. '/' . $images);
        //mengembalikan nama file yg ditampung divariable $images
        return $images;
    }

    public function destroy($id)
    {
        //query select berdasarkan id
        $product = Product::findOrFail($id);

        //cek..jika field photo tidak null
        if (!empty($product->photo))
        {
            //file akan dihapus dari folder uploads/produk
            File::delete(public_path('uploads/product/' . $product->photo));
        }
        //hapus dari data table
        $product->delete();
        return redirect()->back()->with(['success' => '<strong>' . $product->name . '</strong>> Telah Dihapus!']);
    }

    //edit
    public function edit($id)
    {
        //query select berdasarkan id
        $product = Product::findOrFail($id);
        $categories = Category::orderBy('name', 'ASC')->get();
        return view('product.edit', compact('product', 'categories'));
    }

    public function update(Request $request, $id)
    {
        //validasi
        $this->validate($request, [
            'code' => 'required|string|max:10|exists:products,code',
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:100',
            'stock' => 'required|integer',
            'price' => 'required|integer',
            'category_id' => 'required|exists:categories,id',
            'photo' => 'nullable|image|mimes:jpg,png,jpeg'
        ]);

        try{
            //query select berasarkan id
            $product = Product::findOrFail($id);
            $photo = $product->photo;

            //cek jika ada file yang dikirim dari form
            if ($request->hasFile('photo'))
            {
                //cek, jika poto tidak kosong maka file yang di folder uploads/product akan dihapus
                !empty($photo) ? File::delete(public_path('uploads/product' . $photo)):null;
                //uploading file dengan menggunakan method saveFile() yang telah dibuat sebelumnya
                $photo = $this->saveFile($request->name, $request->file('photo'));
            }

            //perbaharui data di database
            $product->update([
                'name' => $request->name,
                'description' => $request->description,
                'stock' => $request->stock,
                'price' => $request->price,
                'category_id' => $request->category_id,
                'photo' => $photo
            ]);

            return redirect(route('produk.index'))
                ->with(['success' => '<strong>' . $product->name . '</strong> Diperbaharui']);
        }catch (\Exception $e){
            return redirect()->back()
                ->with(['error' => $e->getMessage()]);
        }
    }

}
