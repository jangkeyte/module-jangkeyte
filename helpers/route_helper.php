<?php

/*
$model = new App\LineItem;
plural_from_model($model);
=> line-items

plural_from_model(new App\User);
=> users
*/

if (! function_exists('show_route')) {
    function show_route($model, $resource = null)
    {
        $resource = $resource ?? plural_from_model($model);

        return route("{$resource}.show", $model);
    }
}

if (! function_exists('plural_from_model')) {
    function plural_from_model($model)
    {
        $plural = Illuminate\Support\Str::plural(class_basename($model));

        return Illuminate\Support\Str::kebab($plural);
    }
}

if (! function_exists('storeImage')) {
    function storeImage($request) 
    {
        $imageName = '';
        $image_dir = 'storage/uploads/users';
        if($request->hasFile('image')){
            $path = $request->file('image')->store('temp');
            $image = $request->file('image');
            $imageName = $image->getClientOriginalName();
            $image->move(public_path($image_dir), $imageName);
        }
        return $imageName;
    }
}