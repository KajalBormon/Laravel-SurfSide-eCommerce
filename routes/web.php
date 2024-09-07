<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WishlistController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;


Auth::routes();
/* -------------------Home Controller--------------- */
Route::controller(HomeController::class)->group(function(){
    Route::get('/','index')->name('home.index');
    Route::get('/contact','contact')->name('home.contact');
    Route::post('/contact_store','contact_store')->name('home.contact.store');
    Route::get('/search','search')->name('home.search');
});


/* -------------UserController For FrontEnd ----------------*/
Route::middleware(['auth'])->controller(UserController::class)->group(function(){
    Route::get('/user-dashboard', 'index')->name('user.index');
    Route::get('/user/orders','orders')->name('user.orders');
    Route::get('/user/order_details/{id}','order_details')->name('user.order.details');
    Route::put('/user/order_cancel','order_cancel')->name('user.order.cancel');
});

/* ---------------AdminController For BackEnd -----------------*/
Route::middleware(['auth','admin'])->controller(AdminController::class)->group(function(){
    /* -------------Brand Route------------ */
    Route::get('/admin-dashboard', 'index')->name('admin.index');
    Route::get('admin/brands','brands')->name('admin.brands');
    Route::get('admin/brand_add','brand_add')->name('admin.brand_add');
    Route::post('admin/brand_store','brand_store')->name('admin.brand.store');
    Route::get('admin/brand_edit/{id}','brand_edit')->name('admin.brand.edit');
    Route::put('admin/brand_update/{id}','brand_update')->name('admin.brand.update');
    Route::delete('admin/brand_delete/{id}','brand_delete')->name('admin.brand.delete');

    /* ---------------Category Route-------------- */
    Route::get('/admin/categories','category')->name('admin.category');
    Route::get('/admin/category_add','category_add')->name('admin.category.add');
    Route::post('/admin/category_store','category_store')->name('admin.category.store');
    Route::get('/admin/category_update/{id}','category_edit')->name('admin.category.edit');
    Route::post('/admin/category_update/{id}','category_update')->name('admin.category.update');
    Route::delete('/admin/category_delete/{id}','category_delete')->name('admin.category.delete');

    /* ---------------Product Route-------------- */
    Route::get('/admin/products','products')->name('admin.products');
    Route::get('/admin/product_add','product_add')->name('admin.product.add');
    Route::post('/admin/product_store','product_store')->name('admin.product.store');
    Route::get('/admin/product_edit/{id}','product_edit')->name('admin.product.edit');
    Route::put('/admin/product_update/{id}','product_update')->name('admin.product.update');
    Route::delete('/admin/product_delete/{id}','product_delete')->name('admin.product.delete');


    /* ---------------Coupon---------------- */
    Route::get('/admin/coupons','coupons')->name('admin.coupons');
    Route::get('/admin/coupon_add','coupon_add')->name('admin.coupon.add');
    Route::post('/admin/coupon_add','coupon_store')->name('admin.coupon.store');
    Route::get('/admin/coupon_edit/{id}','coupon_edit')->name('admin.coupon.edit');
    Route::put('/admin/coupon_update/{id}','coupon_update')->name('admin.coupon.update');
    Route::delete('/admin/coupon_delete/{id}','coupon_delete')->name('admin.coupon.delete');


    /* ----------------ShowOrder on Admin------------ */
    Route::get('/admin/orders','orders')->name('admin.orders');
    Route::get('/admin/order_details/{id}','order_details')->name('admin.order.details');
    Route::put('/admin/update_order_status','update_order_status')->name('admin.update.order.status');

    /* --------------Slider--------------- */
    Route::get('/admin/slider','sliders')->name('admin.slider');
    Route::get('/admin/slider-add','sliders_add')->name('admin.sliders.add');
    Route::post('/admin/sliders_store','sliders_store')->name('admin.sliders.store');
    Route::get('/admin/slider_edit/{id}','slider_edit')->name('admin.slider.edit');
    Route::put('/admin/slider_update/{id}','slider_update')->name('admin.slider.update');
    Route::delete('/admin/slider_delete/{id}','slider_delete')->name('admin.slider.delete');

    /* ---------------Contact------------------- */
    Route::get('/admin/all-message','contacts')->name('admin.contact');
    Route::delete('admin/contact-delete/{id}','contact_delete')->name('admin.contact.delete');

    /* -------------Search------------ */
    Route::get('/admin/search','search')->name('admin.search');


});

  /* ------------- ShopController----------*/

Route::controller(ShopController::class)->group(function(){
    Route::get('/shop','shop')->name('home.shop');
    Route::get('/product-details/{slug}','shop_details')->name('home.shop.details');
});

/* --------------Shopping Cart Controller----------- */

Route::controller(CartController::class)->group(function(){
    Route::get('/cart','index')->name('shop.cart');
    Route::post('/cart/add','add_to_cart')->name('cart.add');
    Route::put('/cart/increase_quantity/{rowId}','increase_cart_item')->name('cart.qty.increase');
    Route::put('/cart/decrease_quantity/{rowId}','decrease_cart_item')->name('cart.qty.decrease');
    Route::delete('/cart/remove_item/{rowId}','cart_item_remove')->name('cart.remove.item');
    Route::delete('/cart/cart_empty','cart_empty')->name('cart.empty');

    /* ---------Coupon--------- */
    Route::post('/cart/coupon_apply','coupon_apply')->name('cart.coupon.apply');
    Route::delete('/cart/coupon_remove','coupon_remove')->name('cart.coupon.remove');

    /* ----------checkout---------- */
    Route::get('/checkout','checkout')->name('cart.checkout');
    Route::post('/place-an-order','place_an_order')->name('cart.place.an.order');
    Route::get('/order-confirmation','order_confirmation')->name('cart.order.confirmation');

});

/* ----------Wishlist Controller-------- */

Route::controller(WishlistController::class)->group(function(){
    Route::get('/wishlist','index')->name('wishlist');
    Route::post('/wishlist/add','add_to_wishlist')->name('wishlist.add');
    Route::delete('/wishlist_item_delete/{rowId}','remove_item')->name('wishlist.item.remove');
    Route::delete('/wishlist_clear','remove_all')->name('wishlist.clear');
    Route::post('/wishlist/move_to_cart/{rowId}','move_to_cart')->name('wishlist.move.to.cart');
});



