<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Product;
use App\Models\Slider;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        $sliders = Slider::where('status', 1)->get()->take(3);
        $categories = Category::orderBy('name', 'DESC')->get();
        $products = Product::whereNotNull('sale_price')->where('sale_price', '<>', '')->inRandomOrder()->get()->take(8);
        $feature_products = Product::where('featured', 1)->get()->take(8);
        return view('index', compact('sliders', 'categories', 'products', 'feature_products'));
    }

    public function contact()
    {
        return view('contact');
    }

    public function contact_store(Request $request)
    {
        $request->validate([
            'name' => 'required|max:100',
            'phone' => 'required|numeric|digits:11',
            'email' => 'required|email',
            'message' => 'required'
        ]);

        Contact::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'message' => $request->message
        ]);
        return redirect()->back()->with('status', 'Your Message has been sent Successfully');
    }

    /* public function search(Request $request){
        $query = $request->input('query');
        $results = Product::where('name','LIKE',"%{$query}%")->get()->take(8);
        return response()->json($results);

    } */

    public function search(Request $request){
        if ($request->has('query')) {
            $searchquery = $request->get('query');
            $products = Product::where('name', 'LIKE', "%{$searchquery}%")->get();
            return response()->json($products->map(function ($product) {
                return [
                    'name' => $product->name,
                    'slug' => $product->slug,
                    'image' => $product->image,
                    'details_url' => route('home.shop.details', ['slug' => $product->slug]) // Include the URL directly
                ];
            }));
        }
    }
}
