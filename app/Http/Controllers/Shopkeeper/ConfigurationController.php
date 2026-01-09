<?php
namespace App\Http\Controllers\Shopkeeper;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ProductTemplateExport;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use App\Imports\ImportProducts;
use App\Helpers\Common;
use App\Models\Product;
use App\Models\Shopkeeper;
use Illuminate\Support\Str;

use Illuminate\Support\Facades\Storage;
use DB;
use File;
use Route;
use BrowserDetect;

class ConfigurationController extends Controller
{
    public $shopkeeper;
    public function __construct()
    {
        $this->shopkeeper = Auth::guard('shopkeeper')->user();
    }

    public function index()
    {
        $page_title ='Shopkeeper Configuration';
        $shopkeeper = $this->shopkeeper;
        return view('shopkeeper.configuration.index',compact('page_title','shopkeeper'));
    }

    public function products()
    {
        $page_title ='Shopkeeper Products';
        $shopkeeper_id = $this->shopkeeper->id;
        $products= Product::where('shopkeeper_id',$shopkeeper_id)->get();
        return view('shopkeeper.products.index',compact('page_title','products'));
    }

    public function list(Request $request)
    {
        if($request->ajax())
        {
            $searchTerm= $request->searchTerm;
            $shopkeeper_id = $this->shopkeeper->id;
            $tableData = Product::where('shopkeeper_id', $shopkeeper_id)
            ->orderBy('updated_at', 'DESC');
            if ($searchTerm) {
                $tableData->where(function ($query) use ($searchTerm) {
                    $query->where('price', 'LIKE', "%{$searchTerm}%")
                          ->orWhere('name', 'LIKE', "%{$searchTerm}%");
                });
            }
            $tableData=  $tableData->get(); // Ensure you fetch the data
            // dd($tableData);
            return datatables()->of($tableData)
                ->addColumn('qr_code', function ($row) {
                    $qr = $row->qr_code;

                    if (!$qr) {
                        return 'No QR Code';
                    }

                    // Case 1: File path string (starts with "storage/")
                    if (is_string($qr) && Str::startsWith($qr, 'storage/')) {
                        $fullPath = public_path($qr);
                        dd($fullPath);
                        if (file_exists($fullPath)) {
                            return '<img src="' . asset($qr) . '" alt="QR Code" style="width:64px; height:64px;">';
                        }
                    }

                    // Case 2: Binary data (manually created product)
                    if (is_resource($qr) || is_string($qr)) {
                        try {
                            $base64 = 'data:image/png;base64,' . base64_encode($qr);
                            return '<img src="' . $base64 . '" alt="QR Code" style="width:64px; height:64px;">';
                        } catch (\Exception $e) {
                            return 'Invalid QR Code';
                        }
                    }

                    return 'QR Code format unknown';
                })
                ->addColumn('action', function ($row) {
                    return '
                        <div class="d-flex align-items-center">
                            <a href="#" class="btn-tableIcon btnIcon-orange"
                            data-product-id="' . htmlspecialchars($row->id, ENT_QUOTES, 'UTF-8') . '">
                                <i class="fa-regular fa-eye"></i>
                            </a>
                            <a href="#" class="btn-lg-icon icon-bg-green me-1 edit-row-btn"
                            data-product-id="' . htmlspecialchars($row->id, ENT_QUOTES, 'UTF-8') . '">
                                <img src="' . asset('resorts_assets/images/edit.svg') . '" alt="" class="img-fluid" />
                            </a>
                            <a href="#" class="btn-lg-icon icon-bg-red delete-row-btn"
                            data-product-id="' . htmlspecialchars($row->id, ENT_QUOTES, 'UTF-8') . '">
                                <img src="' . asset('resorts_assets/images/trash-red.svg') . '" alt="" class="img-fluid" />
                            </a>
                        </div>';
                })
                ->escapeColumns([])
                ->make(true);
        } 
    }

    public function show($id)
    {
        $shopkeeper_id = $this->shopkeeper->id;
        // dd($shopkeeper_id);
        $product = Product::where('id', $id)
            ->where('shopkeeper_id', $shopkeeper_id)
            ->first();

        if (!$product) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        return response()->json([
            'name' => $product->name,
            'price' => $product->price,
            'qr_code' => $product->qr_code ? base64_encode($product->qr_code) : null, // Return Base64 QR code
        ]);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'products.*.product_name' => 'required|string|max:255',
            'products.*.product_price' => 'required|numeric',
            'products.*.qr_code' => 'required|string',
        ]);

        // Check for existing products
        $shopkeeper_id = $this->shopkeeper->id;
        $existingProducts = [];

        foreach ($validatedData['products'] as $product) {
            $exists = Product::where('shopkeeper_id', $shopkeeper_id)
                            ->where('name', $product['product_name'])
                            ->exists();
            
            if ($exists) {
                $existingProducts[] = $product['product_name'];
            }
        }

        // If any products already exist, return error
        if (!empty($existingProducts)) {
            return response()->json([
                'success' => false,
                'message' => 'The following products already exist: ' . implode(', ', $existingProducts),
                'existingProducts' => $existingProducts
            ], 422);
        }

        // Create products if none exist
        foreach ($validatedData['products'] as $product) {
            Product::create([
                'shopkeeper_id' => $shopkeeper_id,
                'name' => $product['product_name'],
                'price' => $product['product_price'],
                'qr_code' => base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $product['qr_code'])),
            ]);
        }

        return response()->json(['success' => true, 'message' => 'Products added successfully!']);
    }

    public function destroy($id)
    {
        $shopkeeper_id = $this->shopkeeper->id;

        $product = Product::where('id', $id)
            ->where('shopkeeper_id', $shopkeeper_id)
            ->first();

        if (!$product) {
            return response()->json([
                'success' => false,
                'msg' => 'Product not found or not authorized to delete',
            ], 404);
        }

        $product->delete();

        return response()->json([
            'success' => true,
            'msg' => 'Product deleted successfully',
        ]);
    }

    public function inlineUpdate(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric',
            'qr_code' => 'required|string', // Ensure QR code is passed
        ]);

        $product = Product::findOrFail($id);
        $product->name = $request->name;
        $product->price = $request->price;

        // Save the QR code from the frontend (base64 string)
        $qrCodeBase64 = $request->qr_code;

        // Ensure QR code exists and is a valid string
        if (empty($qrCodeBase64)) {
            return response()->json([
                'success' => false,
                'msg' => 'QR Code is required.'
            ], 400);
        }

        // Decode the Base64 string and save the image
        $qrCodeImage = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $qrCodeBase64));

        // Save the QR code image as binary
        $product->qr_code = $qrCodeImage;

        $product->save();

        return response()->json([
            'success' => true,
            'msg' => 'Product updated successfully!',
        ]);
    }

    public function exportProducts(Request $request)
    {
        return Excel::download(new ProductTemplateExport(), 'productTemplate.xlsx');
    }

    // public function importProducts(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'ImportProducts' => 'required|file|mimes:xls,xlsx',
    //     ], [
    //         'ImportProducts.mimes' => 'The file must be of type: xls, xlsx.',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
    //     }

    //     // Store file temporarily
    //     $filePath = $request->file('ImportProducts')->store('imports');

    //     // Dispatch job for processing
    //     ImportProductsJob::dispatch($filePath, auth()->id());

    //     return response()->json([
    //         'success' => true,
    //         'message' => "Products are being imported. Check back shortly.",
    //     ]);
    // }

    public function importPreview(Request $request)
    {
        $request->validate([
            'ImportProducts' => 'required|mimes:xlsx,xls,csv'
        ]);

        $import = new ImportProducts();
        Excel::import($import, $request->file('ImportProducts'));

        if ($import->rows->isEmpty()) {
            return response()->json([
                'success' => false,
                'products' => [],
                'message' => 'No valid rows found.'
            ]);
        }

        $products = $import->rows->map(function ($row, $index) {
            return [
                'product_name' => $row[0] ?? '',
                'product_price' => $row[1] ?? '',
            ];
        });

        return response()->json([
            'success' => true,
            'products' => $products
        ]);
    }

    public function submit(Request $request)
    {
        $validated = $request->validate([
            'products' => 'required|array',
            'products.*.product_name' => 'required|string|max:255',
            'products.*.product_price' => 'required|numeric',
            'products.*.qr_code' => 'required|string',
        ]);

        $shopkeeper_id = $this->shopkeeper->id;
        $updated = 0;
        $created = 0;

        foreach ($validated['products'] as $product) {
            // Decode QR image from base64
            $qrImage = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $product['qr_code']));
            
            // Check if product with same name exists
            $existingProduct = Product::where('shopkeeper_id', $shopkeeper_id)
                                      ->where('name', $product['product_name'])
                                      ->first();
                                      
            if ($existingProduct) {
                // Update existing product
                $existingProduct->price = $product['product_price'];
                $existingProduct->qr_code = $qrImage;
                $existingProduct->save();
                $updated++;
            } else {
                // Create new product
                Product::create([
                    'shopkeeper_id' => $shopkeeper_id,
                    'name' => $product['product_name'],
                    'price' => $product['product_price'],
                    'qr_code' => $qrImage,
                ]);
                $created++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Products processed successfully! Created: $created, Updated: $updated"
        ]);
    }

}