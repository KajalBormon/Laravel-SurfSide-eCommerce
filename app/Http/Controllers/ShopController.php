<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    public function shop(Request $request){
        $size = $request->query('size') ? $request->query('size') : 12;
        $order = $request->query('order') ? $request->query('order') : -1;
        $o_column = "";
        $o_order = "";
        $min_price = $request->query('min') ? $request->query('min') : 1;
        $max_price = $request->query('max') ? $request->query('max') : 500;
        switch($order){
            case 1:
                $o_column = 'created_at';
                $o_order = 'DESC';
                break;
            case 2:
                $o_column = 'created_at';
                $o_order = 'ASC';
                break;
            case 3:
                $o_column = 'sale_price';
                $o_order = 'ASC';
                break;
            case 4:
                $o_column = 'sale_price';
                $o_order = 'DESC';
                break;

                default:
                $o_column = 'id';
                $o_order = 'DESC';
                break;
        }
        $categories = Category::orderBy('id','ASC')->get();
        $category_filter = $request->query('categories');
        $filter_brands = $request->query('brands');
        $brands = Brand::orderBy('name','ASC')->get();

        $products = Product::Where(function($query) use($filter_brands){
            $query->WhereIn('brand_id',explode(',',$filter_brands))->orWhereRAW("'".$filter_brands."'=''");
        })->Where(function($query) use($category_filter){
            $query->WhereIn('category_id',explode(',',$category_filter))->orWhereRAW("'".$category_filter."'=''");
        })->where(function($query) use($min_price, $max_price){
            $query->whereBetween('regular_price',[$min_price, $max_price])
            ->orWhereBetween('sale_price',[$min_price, $max_price]);
        })->orderBy($o_column, $o_order)->paginate($size);

        return view('shop',compact('products','size','order','brands','filter_brands','categories','category_filter','min_price','max_price'));
    }

    public function shop_details($product_slug){
        $product = Product::where('slug',$product_slug)->first();
        $related_products = Product::where('slug','<>',$product_slug)->get()->take(8);
        return view('products-details',compact('product','related_products'));
    }
}
