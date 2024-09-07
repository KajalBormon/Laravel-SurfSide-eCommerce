<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Contact;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Slider;
use App\Models\Transaction;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Intervention\Image\Laravel\Facades\Image;


class AdminController extends Controller
{
    public function index()
    {
        $orders = Order::orderBy('created_at', 'DESC')->get()->take(10);
        $dashboardData = DB::select("select sum(total) AS TotalAmount,
                                    sum(if(status='ordered',total,0)) AS TotalOrderedAmount,
                                    sum(if(status='delivered',total,0)) AS TotalDeliveredAmount,
                                    sum(if(status='canceled',total,0)) AS TotalCanceledAmount,
                                    count(*) AS Total,
                                    sum(if(status='ordered',1,0)) AS TotalOrdered,
                                    sum(if(status='delivered',1,0)) AS TotalDelivered,
                                    sum(if(status='canceled',1,0)) AS TotalCanceled
                                    From orders



        ");

        $monthlyData = DB::select("

            SELECT M.id AS MonthNo, M.name AS MonthName,
            IFNULL(D.TotalAmount,0) AS TotalAmount,
            IFNULL(D.TotalOrderedAmount,0) AS TotalOrderedAmount,
            IFNULL(D.TotalDeliveredAmount,0) AS TotalDeliveredAmount,
            IFNULL(D.TotalCanceledAmount,0) AS TotalCanceledAmount
            FROM month_names M
            LEFT JOIN(SELECT DATE_FORMAT(created_at,'%b') AS MonthName,
            MONTH(created_at) AS MonthNo,
            sum(total) AS TotalAmount,
            sum(if(status='ordered',total,0)) AS TotalOrderedAmount,
            sum(if(status='delivered',total,0)) AS TotalDeliveredAmount,
            sum(if(status='canceled',total,0)) AS TotalCanceledAmount
            FROM Orders WHERE YEAR(created_at) = YEAR(Now()) GROUP BY YEAR(created_at),
            MONTH(created_at), DATE_FORMAT(created_at,'%b') ORDER BY MONTH(created_at))
            D On D.MonthNo=M.id

        ");
        $AmountM = implode(',', collect($monthlyData)->pluck('TotalAmount')->toArray());
        $OrderAmountM = implode(',', collect($monthlyData)->pluck('TotalOrderedAmount')->toArray());
        $DeliveredAmountM = implode(',', collect($monthlyData)->pluck('TotalDeliveredAmount')->toArray());
        $CanceledAmount = implode(',', collect($monthlyData)->pluck('TotalCanceledAmount')->toArray());

        $TotalAmount = collect($monthlyData)->sum('TotalAmount');
        $TotalOrderedAmount = collect($monthlyData)->sum('TotalOrderedAmount');
        $TotalDeliveredAmount = collect($monthlyData)->sum('TotalDeliveredAmount');
        $TotalCanceledAmount = collect($monthlyData)->sum('TotalCanceledAmount');

        return view('admin.index', compact('orders', 'dashboardData', 'AmountM', 'OrderAmountM', 'DeliveredAmountM', 'CanceledAmount', 'TotalAmount', 'TotalOrderedAmount', 'TotalDeliveredAmount', 'TotalCanceledAmount'));
    }


    /* ----------------Brand Information---------------- */

    public function brands()
    {
        $brands = Brand::orderBy('id', 'DESC')->paginate(10);
        return view('admin.brands', compact('brands'));
    }

    public function brand_add()
    {
        return view('admin.brand-add');
    }

    public function brand_store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'slug' => 'required|unique:brands,slug',
            'image' => 'mimes:png,jpg,jpeg,webp|max:2048'
        ]);

        $brand = new Brand();
        $brand->name = $request->name;
        $brand->slug = Str::slug($request->name);
        $image = $request->file('image');
        $file_extension = $request->file('image')->extension();
        $file_name = Carbon::now()->timestamp . '.' . $file_extension;
        $this->GenerateBrandThumbnailImage($image, $file_name);
        $brand->image = $file_name;
        $brand->save();
        return redirect()->route('admin.brands')->with('status', 'Brand has been Added Successfully');
    }

    public function brand_edit($id)
    {
        $brand = Brand::find($id);
        return view('admin.brand-edit', compact('brand'));
    }

    public function brand_update(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'slug' => 'required|unique:brands,slug,' . $request->id,
            'image' => 'mimes:png,jpg,jpeg,webp|max:2048'
        ]);

        $brand = Brand::find($request->id);
        $brand->name = $request->name;
        $brand->slug = Str::slug($request->name);
        if ($request->hasFile('image')) {
            if (File::exists(public_path('uploads/brands') . '/' . $brand->image)) {
                File::delete(public_path('uploads/brands') . '/' . $brand->image);
            }

            $image = $request->file('image');
            $file_extension = $request->file('image')->extension();
            $file_name = Carbon::now()->timestamp . '.' . $file_extension;
            $this->GenerateBrandThumbnailImage($image, $file_name);
            $brand->image = $file_name;
        }

        $brand->save();
        return redirect()->route('admin.brands')->with('status', 'Brand has been Updated Successfully');
    }

    public function GenerateBrandThumbnailImage($image, $ImageName)
    {
        $destinationPath = public_path('uploads/brands');
        $img = Image::read($image->path());
        $img->cover(124, 124, "top");
        $img->resize(124, 124, function ($constraint) {
            $constraint->aspectRatio();
        })->save($destinationPath . '/' . $ImageName);
    }

    public function brand_delete($id)
    {
        $brand = Brand::find($id);
        if (File::exists(public_path('uploads/brands') . '/' . $brand->image)) {
            File::delete(public_path('uploads/brands') . '/' . $brand->image);
        }
        $brand->delete();
        return redirect()->route('admin.brands')->with('status', 'Brand has been deleted Successfully');
    }


    /* ---------------------Category Information-------------------- */

    public function category()
    {
        $categories = Category::orderBy('id', 'DESC')->paginate(10);
        return view('admin.categories', compact('categories'));
    }

    public function category_add()
    {
        return view('admin.category-add');
    }

    public function category_store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'slug' => 'required|unique:categories,slug,' . $request->id,
            'image' => 'mimes:png,jpg,jpeg,webp|max:2048'
        ]);
        $category = new Category();
        $category->name = $request->name;
        $category->slug = Str::slug($request->name);
        $image = $request->file('image');
        $file_extension = $request->file('image')->extension();
        $file_name = Carbon::now()->timestamp . '.' . $file_extension;
        $this->GenerateCategoryThumbnailImage($image, $file_name);
        $category->image = $file_name;
        $category->save();
        return redirect()->route('admin.category')->with('status', 'Category has been Added Successfully');
    }

    public function GenerateCategoryThumbnailImage($image, $ImageName)
    {
        $destinationPath = public_path('uploads/categories');
        $img = Image::read($image->path());
        $img->cover(124, 124, "top");
        $img->resize(124, 124, function ($constraint) {
            $constraint->aspectRatio();
        })->save($destinationPath . '/' . $ImageName);
    }

    public function category_edit($id)
    {
        $category = Category::find($id);
        return view('admin.category-edit', compact('category'));
    }

    public function category_update(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'slug' => 'required|unique:brands,slug,' . $request->id,
            'image' => 'mimes:png,jpg,jpeg,webp|max:2048'
        ]);

        $category = Category::find($request->id);
        $category->name = $request->name;
        $category->slug = $request->slug;
        if ($request->hasFile('image')) {
            if (File::exists(public_path('uploads/categories') . '/' . $category->image)) {
                File::delete(public_path('uploads/categories') . '/' . $category->image);
            }

            $image = $request->file('image');
            $file_extension = $image->extension();
            $file_name = Carbon::now()->timestamp . '-' . $file_extension;
            $this->GenerateCategoryThumbnailImage($image, $file_name);
            $category->image = $file_name;
        }
        $category->save();
        return redirect()->route('admin.category')->with('status', 'Category Updated Successfully');
    }

    public function category_delete($id)
    {
        $category = Category::find($id);
        if (File::exists(public_path('uploads/categories') . '/' . $category->image)) {
            File::delete(public_path('uploads/categories') . '/' . $category->image);
        }
        $category->delete();
        return redirect()->route('admin.category')->with('status', 'Category has been deleted Successfully');
    }

    /* -------------------------product Information--------------------- */

    public function products()
    {
        $products = Product::orderBy('created_at', 'DESC')->paginate(10);
        return view('admin.products', compact('products'));
    }

    public function product_add()
    {
        $categories = Category::select('id', 'name')->orderBy('id')->get();
        $brands = Brand::select('id', 'name')->orderBy('id')->get();

        return view('admin.product-add', compact('categories', 'brands'));
    }

    public function product_store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'slug' => 'required|unique:products,slug',
            'short_description' => 'required',
            'description' => 'required',
            'regular_price' => 'required',
            'sale_price' => 'required',
            'SKU' => 'required',
            'stock_status' => 'required',
            'featured' => 'required',
            'quantity' => 'required',
            'image' => 'required|mimes:jpg,png,jpeg|max:2048',
            'category_id' => 'required',
            'brand_id' => 'required'
        ]);

        $product = new Product();
        $product->name = $request->name;
        $product->slug = Str::slug($request->name);
        $product->short_description = $request->short_description;
        $product->description = $request->description;
        $product->regular_price = $request->regular_price;
        $product->sale_price = $request->sale_price;
        $product->SKU = $request->SKU;
        $product->stock_status = $request->stock_status;
        $product->featured = $request->featured;
        $product->quantity = $request->quantity;
        $product->category_id = $request->category_id;
        $product->brand_id = $request->brand_id;
        $current_timestamp = Carbon::now()->timestamp;
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $image_name = $current_timestamp . '-' . $image->extension();
            $this->GenerateProductThumbnailImage($image, $image_name);
            $product->image = $image_name;
        }

        $gallery_array = array();
        $gallery_images = "";
        $counter = 1;
        if ($request->hasFile('images')) {
            $allowed_extension = ['jpg', 'jpeg', 'png'];
            $files = $request->file('images');

            foreach ($files as $file) {
                $gallery_extension = $file->getClientOriginalExtension();
                $gallery_extension_check = in_array($gallery_extension, $allowed_extension);

                if ($gallery_extension_check) {
                    $gallery_image_name = $current_timestamp . '-' . $counter . '-' . $gallery_extension;
                    $this->GenerateProductThumbnailImage($file, $gallery_image_name);
                    array_push($gallery_array, $gallery_image_name);
                    $counter = $counter + 1;
                }
            }
            $gallery_images = implode(',', $gallery_array);
            $product->images = $gallery_images;
        }

        $product->save();
        return redirect()->route('admin.products')->with('status', 'Product has been added Successfully');
    }


    public function GenerateProductThumbnailImage($image, $ImageName)
    {
        $destinationPath = public_path('uploads/products');
        $destinationPathThumbnail = public_path('uploads/products/thumbnails');
        $img = Image::read($image->path());
        $img->cover(580, 689, "top");
        $img->resize(580, 689, function ($constraint) {
            $constraint->aspectRatio();
        })->save($destinationPath . '/' . $ImageName);

        $img->resize(104, 104, function ($constraint) {
            $constraint->aspectRatio();
        })->save($destinationPathThumbnail . '/' . $ImageName);
    }

    public function product_edit($id)
    {
        $product = Product::find($id);
        $categories = Category::select('id', 'name')->orderBy('id')->get();
        $brands = Brand::select('id', 'name')->orderBy('id')->get();
        return view('admin.product-edit', compact('product', 'categories', 'brands'));
    }

    public function product_update(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'slug' => 'required|unique:products,slug,' . $request->id,
            'short_description' => 'required',
            'description' => 'required',
            'regular_price' => 'required',
            'sale_price' => 'required',
            'SKU' => 'required',
            'stock_status' => 'required',
            'featured' => 'required',
            'quantity' => 'required',
            'image' => 'mimes:jpg,png,jpeg|max:2048',
            'category_id' => 'required',
            'brand_id' => 'required'
        ]);

        $product = Product::find($request->id);
        $product->name = $request->name;
        $product->slug = Str::slug($request->name);
        $product->short_description = $request->short_description;
        $product->description = $request->description;
        $product->regular_price = $request->regular_price;
        $product->sale_price = $request->sale_price;
        $product->SKU = $request->SKU;
        $product->stock_status = $request->stock_status;
        $product->featured = $request->featured;
        $product->quantity = $request->quantity;
        $product->category_id = $request->category_id;
        $product->brand_id = $request->brand_id;
        $current_timestamp = Carbon::now()->timestamp;

        if ($request->hasFile('image')) {
            if (File::exists(public_path('uploads/products') . '/' . $product->image)) {
                File::delete(public_path('uploads/products') . '/' . $product->image);
            }
            if (File::exists(public_path('uploads/products/thumbnails') . '/' . $product->image)) {
                File::delete(public_path('uploads/products/thumbnails') . '/' . $product->image);
            }
            $image = $request->file('image');
            $image_name = $current_timestamp . '-' . $image->extension();
            $this->GenerateProductThumbnailImage($image, $image_name);
            $product->image = $image_name;
        }

        $gallery_array = array();
        $gallery_images = "";
        $counter = 1;
        if ($request->hasFile('images')) {
            foreach (explode(',', $product->images) as $thumb_images) {
                if (File::exists(public_path('uploads/products') . '/' . $thumb_images)) {
                    File::delete(public_path('uploads/products') . '/' . $thumb_images);
                }
                if (File::exists(public_path('uploads/products/thumbnails') . '/' . $thumb_images)) {
                    File::delete(public_path('uploads/products/thumbnails') . '/' . $thumb_images);
                }
            }
            $allowed_extension = ['jpg', 'jpeg', 'png'];
            $files = $request->file('images');

            foreach ($files as $file) {
                $gallery_extension = $file->getClientOriginalExtension();
                $gallery_extension_check = in_array($gallery_extension, $allowed_extension);
                if ($gallery_extension_check) {
                    $gallery_image_name = $current_timestamp . '-' . $counter . '-' . $gallery_extension;
                    $this->GenerateProductThumbnailImage($file, $gallery_image_name);
                    array_push($gallery_array, $gallery_image_name);
                    $counter = $counter + 1;
                }
            }
            $gallery_images = implode(',', $gallery_array);
            $product->images = $gallery_images;
        }

        $product->save();
        return redirect()->route('admin.products')->with('status', 'Product has been added Successfully');
    }

    public function product_delete($id)
    {
        $product = Product::find($id);
        if (File::exists(public_path('uploads/products') . '/' . $product->image)) {
            File::delete(public_path('uploads/products') . '/' . $product->image);
        }
        if (File::exists(public_path('uploads/products/thumbnails') . '/' . $product->image)) {
            File::delete(public_path('uploads/products/thumbnails') . '/' . $product->image);
        }

        foreach (explode(',', $product->images) as $thumb_images) {
            if (File::exists(public_path('uploads/products') . '/' . $thumb_images)) {
                File::delete(public_path('uploads/products') . '/' . $thumb_images);
            }
            if (File::exists(public_path('uploads/products/thumbnails') . '/' . $thumb_images)) {
                File::delete(public_path('uploads/products/thumbnails') . '/' . $thumb_images);
            }
        }

        $product->delete();
        return redirect()->route('admin.products')->with('status', 'Product has been deleted Successfully');
    }


    /* -------Coupon-------- */
    public function coupons()
    {
        $coupons = Coupon::orderBy('expiry_date')->paginate(12);
        return view('admin.coupons', compact('coupons'));
    }

    public function coupon_add()
    {
        return view('admin.coupon-add');
    }


    public function coupon_store(Request $request)
    {
        $request->validate([
            'code' => 'required',
            'type' => 'required',
            'value' => 'required|numeric',
            'cart_value' => 'required|numeric',
            'expiry_date' => 'required|date'

        ]);

        Coupon::create([
            'code' => $request->code,
            'type' => $request->type,
            'value' => $request->value,
            'cart_value' => $request->cart_value,
            'expiry_date' => $request->expiry_date
        ]);
        return redirect()->route('admin.coupons')->with('status', 'Coupon has been added Successfully');
    }


    public function coupon_edit($id)
    {
        $coupon = Coupon::find($id);
        return view('admin.coupon-edit', compact('coupon'));
    }


    public function coupon_update(Request $request)
    {
        $request->validate([
            'code' => 'required',
            'type' => 'required',
            'value' => 'required|numeric',
            'cart_value' => 'required|numeric',
            'expiry_date' => 'required|date'
        ]);

        $coupon = Coupon::find($request->id);
        $coupon->update([
            'code' => $request->code,
            'type' => $request->type,
            'value' => $request->value,
            'cart_value' => $request->cart_value,
            'expiry_date' => $request->expiry_date
        ]);
        return redirect()->route('admin.coupons')->with('status', 'Coupon has been Updated Successfully');
    }

    public function coupon_delete($id)
    {
        $coupon = Coupon::find($id);
        $coupon->delete();
        return redirect()->route('admin.coupons')->with('status', 'Coupon has been Deleted Successfully');
    }


    /* ---------------ShowOrder---------------- */

    public function orders()
    {
        $orders = Order::orderBy('created_at')->paginate(12);
        return view('admin.orders', compact('orders'));
    }

    /* --------Order Details----------- */

    public function order_details($order_id)
    {
        $order = Order::find($order_id);
        $orderItems = OrderItem::where('order_id', $order_id)->orderBy('id')->paginate(12);
        $transaction = Transaction::where('order_id', $order_id)->first();
        return view('admin.order-details', compact('order', 'orderItems', 'transaction'));
    }

    public function update_order_status(Request $request)
    {
        $order = Order::find($request->order_id);
        $order->status = $request->order_status;
        if ($request->order_status == 'delivered') {
            $order->delivered_date = Carbon::now();
        } elseif ($request->order_status == 'canceled') {
            $order->canceled_date = Carbon::now();
        }
        $order->save();

        if ($request->order_status == 'delivered') {
            $transaction = Transaction::where('order_id', $request->order_id)->first();
            $transaction->status = 'approved';
            $transaction->save();
        }
        return back()->with('status', 'Update Order Status');
    }


    /* ---------Slider-------- */

    public function sliders()
    {
        $sliders = Slider::orderBy('created_at', 'DESC')->paginate(12);
        return view('admin.sliders', compact('sliders'));
    }

    public function sliders_add()
    {
        return view('admin.sliders-add');
    }

    public function sliders_store(Request $request)
    {
        $request->validate([
            'tagline' => 'required',
            'title' => 'required',
            'subtitle' => 'required',
            'link' => 'required',
            'status' => 'required',
            'image' => 'required|mimes:jpg,jpeg,png|max:2048'
        ]);

        $slider = new Slider();
        $slider->tagline = $request->tagline;
        $slider->title = $request->title;
        $slider->subtitle = $request->subtitle;
        $slider->link = $request->link;
        $slider->status = $request->status;

        $image = $request->file('image');
        $file_extension = $request->file('image')->extension();
        $file_name = Carbon::now()->timestamp . '.' . $file_extension;
        $this->GenerateSliderThumbnailImage($image, $file_name);
        $slider->image = $file_name;
        $slider->save();
        return redirect()->route('admin.slider')->with('status', 'Slider has been added successfully ');
    }

    public function GenerateSliderThumbnailImage($image, $ImageName)
    {
        $destinationPath = public_path('uploads/sliders');
        $img = Image::read($image->path());
        $img->cover(400, 690, "top");
        $img->resize(400, 690, function ($constraint) {
            $constraint->aspectRatio();
        })->save($destinationPath . '/' . $ImageName);
    }

    public function slider_edit($id)
    {
        $slider = Slider::find($id);
        return view('admin.slider-edit', compact('slider'));
    }

    public function slider_update(Request $request)
    {
        $request->validate([
            'tagline' => 'required',
            'title' => 'required',
            'subtitle' => 'required',
            'link' => 'required',
            'status' => 'required',
        ]);

        $slider = Slider::find($request->id);
        $slider->tagline = $request->tagline;
        $slider->title = $request->title;
        $slider->subtitle = $request->subtitle;
        $slider->link = $request->link;
        $slider->status = $request->status;

        if ($request->hasFile('image')) {
            if (File::exists(public_path('uploads/sliders') . '/' . $slider->image)) {
                File::delete(public_path('uploads/sliders') . '/' . $slider->image);
            }

            $image = $request->file('image');
            $file_extension = $image->extension();
            $file_name = Carbon::now()->timestamp . '-' . $file_extension;
            $this->GenerateSliderThumbnailImage($image, $file_name);
            $slider->image = $file_name;
        }
        $slider->save();
        return redirect()->route('admin.slider')->with('status', 'Slider has been updated successfully');
    }

    public function slider_delete($id)
    {
        $slider = Slider::find($id);
        if (File::exists(public_path('uploads/sliders') . '/' . $slider->image)) {
            File::delete(public_path('uploads/sliders') . '/' . $slider->image);
        }
        $slider->delete();
        return redirect()->back()->with('status', 'Slider has been deleted successfully');
    }


    public function contacts()
    {
        $contacts = Contact::orderBy('created_at', 'DESC')->paginate(12);
        return view('admin.contact', compact('contacts'));
    }

    public function contact_delete($id)
    {
        $contact = Contact::find($id);
        $contact->delete();
        return redirect()->back()->with('status', 'Contact has been deleted successfully');
    }

    public function search(Request $request){
        if ($request->has('query')) {
            $searchquery = $request->get('query');
            $products = Product::where('name', 'LIKE', "%{$searchquery}%")->get();
            return response()->json($products->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'slug' => $product->slug,
                    'image' => $product->image,
                    'details_url' => route('admin.product.details', ['id' => $product->id])
                ];
            }));
        }
    }
}
