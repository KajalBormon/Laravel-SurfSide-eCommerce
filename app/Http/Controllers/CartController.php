<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Surfsidemedia\Shoppingcart\Facades\Cart;

class CartController extends Controller
{
    public function index(){
        $cart_items = Cart::instance('cart')->content();
        return view('cart',compact('cart_items'));
    }

    public function add_to_cart(Request $request){
        Cart::instance('cart')->add($request->id,$request->name,$request->quantity,$request->price)->associate('App\Models\Product');
        return redirect()->back();
    }

    public function increase_cart_item($rowId){
        $product = Cart::instance('cart')->get($rowId);
        $qty = $product->qty + 1;
        Cart::instance('cart')->update($rowId, $qty);
        return redirect()->back();
    }

    public function decrease_cart_item($rowId){
        $product = Cart::instance('cart')->get($rowId);
        $qty = $product->qty - 1;
        Cart::instance('cart')->update($rowId, $qty);
        return redirect()->back();
    }

    public function cart_item_remove($rowId){
        Cart::instance('cart')->remove($rowId);
        return redirect()->back();
    }

    public function cart_empty(){
        Cart::instance('cart')->destroy();
        return redirect()->back();
    }

    /* ---------------Coupon Applied-------------- */

    public function coupon_apply(Request $request){
        $coupon_code = $request->coupon_code;
        if(isset($coupon_code)){
           $coupon = Coupon::where('code',$coupon_code)->where('expiry_date','>=',Carbon::today())->where('cart_value','<=',Cart::instance('cart')->subtotal())->first();

            if(!$coupon){
                return redirect()->back()->with('error','Invalid Coupon Code');
            }else{
                Session::put('coupon',[
                    'code' => $coupon->code,
                    'type' => $coupon->type,
                    'value' => $coupon->value,
                    'cart_value' => $coupon->cart_value
                ]);
                $this->calculateDiscount();
                return redirect()->back()->with('success','Coupon has been applied successfully');
            }
        }else{
            return redirect()->back()->with('error','Invalid Coupon Code');
        }
    }

    public function calculateDiscount(){
        $discount = 0;
        if(Session::has('coupon')){
            if(Session::get('coupon')['type']=='fixed'){
                $discount = Session::get('coupon')['value'];
            }else{
                $discount = (Cart::instance('cart')->subtotal() * Session::get('coupon')['value'])/100;
            }

            $subTotalAfterDiscount = Cart::instance('cart')->subtotal() - $discount;
            $taxAfterDiscount = ($subTotalAfterDiscount * config('cart.tax'))/100;
            $totalAfterDiscount = $subTotalAfterDiscount + $taxAfterDiscount;

            Session::put('discounts',[
                'discount' => number_format(floatval($discount),2,'.',''),
                'subtotal' => number_format(floatval($subTotalAfterDiscount),2,'.',''),
                'tax' => number_format(floatval($taxAfterDiscount),2,'.',''),
                'total' => number_format(floatval($totalAfterDiscount),2,'.','')
            ]);
        }
    }

    public function coupon_remove(){
        Session::forget('coupon');
        Session::forget('discounts');

        return back()->with('success','Coupon has been removed !!');
    }


    /* --------------CheckOut------------------- */
    public function checkout(){
        if(!Auth::check()){
            return redirect()->route('login');
        }

        $address = Address::where('user_id',Auth::user()->id)->where('isdefault',1)->first();
        return view('checkout',compact('address'));
    }


    public function place_an_order(Request $request){
        $user_id = Auth::user()->id;
        $address = Address::where('id',$user_id)->where('isdefault',true)->first();

        if(!$address){
            $request->validate([
                'name' => 'required|max:100',
                'phone' => 'required|numeric|digits:11',
                'zip' => 'required|numeric|digits:4',
                'state' => 'required',
                'city' => 'required',
                'address' => 'required',
                'locality' => 'required',
                'landmark' => 'required'
            ]);

            $address = new Address();
            $address->name = $request->name;
            $address->phone = $request->phone;
            $address->zip = $request->zip;
            $address->state = $request->state;
            $address->city = $request->city;
            $address->address = $request->address;
            $address->locality = $request->locality;
            $address->landmark = $request->landmark;
            $address->country = "Bangladesh";
            $address->user_id = $user_id;
            $address->isdefault = true;

            $address->save();

        }
        $this->setAmountForCheckout();

        $order = Order::create([
            'user_id' => $user_id,
            'subtotal' => Session::get('checkout')['subtotal'],
            'discount' => Session::get('checkout')['discount'],
            'tax' => Session::get('checkout')['tax'],
            'total' => Session::get('checkout')['total'],
            'name' => $address->name,
            'phone' => $address->phone,
            'locality' => $address->locality,
            'address' => $address->address,
            'city' => $address->city,
            'state' => $address->state,
            'country' => $address->country,
            'landmark' => $address->landmark,
            'zip' => $address->zip
        ]);

        foreach(Cart::instance('cart')->content() as $item){
            $orderItem = new OrderItem();
            $orderItem->product_id = $item->id;
            $orderItem->order_id = $order->id;
            $orderItem->price = $item->price;
            $orderItem->quantity = $item->qty;
            $orderItem->save();
        }
        if($request->mode=="card"){
            //
        }
        elseif($request->mode=="paypal"){
            //
        }
        elseif($request->mode=="cod"){
            $transaction = new Transaction();
            $transaction->user_id = $user_id;
            $transaction->order_id = $order->id;
            $transaction->mode = $request->mode;
            $transaction->status = 'pending';
            $transaction->save();
        }


        Cart::instance('cart')->destroy();
        Session::forget('coupon');
        Session::forget('checkout');
        Session::forget('discounts');
        Session::put('order_id', $order->id);
        return redirect()->route('cart.order.confirmation');

    }


    public function setAmountForCheckout(){
        if(!Cart::instance('cart')->count()>0){
            Session::forget('checkout');
            return;
        }

        if(Session::has('coupon')){
            Session::put('checkout',[
                'discount' => Session::get('discounts')['discount'],
                'subtotal' => Session::get('discounts')['subtotal'],
                'tax' => Session::get('discounts')['tax'],
                'total' => Session::get('discounts')['total'],
            ]);
        }else{
            Session::put('checkout',[
                'discount' => 0,
                'subtotal' => Cart::instance('cart')->subtotal(),
                'tax' => Cart::instance('cart')->tax(),
                'total' => Cart::instance('cart')->total(),
            ]);
        }

    }

    public function order_confirmation(){
        if(Session::has('order_id')){
            $order = Order::find(Session::get('order_id'));
            return view('order-confirmation',compact('order'));
        }
        return redirect()->route('shop.cart');

    }

}
