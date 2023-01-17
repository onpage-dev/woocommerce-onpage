# On Page Woocommerce Plugin

# Intro
This plugin is used to import a project snapshot into your woocommerce website. It uses the wordpress tables so you can use it the way you are used to. __All field data is saved into the object meta table.__
You can create a project snapshot (and the corresponding token) using the __Snapshot__ feature in OnPage.

# Handling data
When you import your snapshots, the plugin will generate [Eloquent Models](https://laravel.com/docs/7.x/eloquent) for your data, in the plugin directory `db-models/` these models are updated every time you import your data.

You can view the models generated for your project in the plugin import page. For each model, you'll find the list of relations and fields imported.


## Configure how to import each resource
Before importing the data, you should specify how to import each resource (in your `functions.php`).
Resources that are not listed here will still be imported in a custom `op_things` table (which makes them invisible to wordpress, and faster to import).
You should only list those resources that will have their own page.
For example, if you plan to have a page for each "Category" and one for each "Product", you can do it as follows (the format is "resource alias" => "import method").
```
add_filter('op_resource_types', function() {
  return [
    'product' => 'post', // "post" = woocommerce product
    'category' => 'term', // "term" = woocommerce category
    // all other resources will be imported in a hidden plugin table with high performance
  ];
});
```


## Selecting data
For instance, assuming you want to select all your products (codenamed `Product`), you can run:
```php
$prods = Op\Product::get(); // a collection of products
```

By default, no ordering is used (fastest method).
The order is only applied when accessing relations (e.g. `$product->variants()->get()`), and not for global order.
If you need to maintain On Page global order, you should also add `->sorted()` to the query like so:
```php
$prods = Op\Product::sorted()->get(); // a collection of products sorted with the global ordering
```

You can also sort results by field value:
```php

$prods = Op\Product::orderByField('price')->get();

// Sort in descending order:
$prods = Op\Product::orderByField('price', 'desc')->get();
```

### Getting record values
You can get each record values through the `->val(field_name, language)` function:
```php
foreach ($prods as $prod) {
  // get the name in the current language
  echo $prod->val('name')."<br>\n";
  // get the name in a custom language
  echo $prod->val('name', 'zh')."<br>\n";
  // access relation values (will return the value from the first relation)
  echo $prod->val('category.name')."<br>\n";
  // Gets a file name
  echo $p->file('info_file')->name; // e.g. MK100.pdf
  // Gets the original image/file url
  echo '<a href="'. $p->file('info_file')->link() .'">Download PDF</a>'
  // This will generate open the file in the browser instead of downloading it
  echo '<a href="'. $p->file('info_file')->link(true) .'">Open PDF</a>'
  // Resize image to width 100 and automatic height (generated in run-time and cached)
  echo '<img src="'. $p->file('cover')->thumb(100) .'">'
  // Generate thumbnail cropping (zooming) the image
  echo '<img src="'. $p->file('cover')->thumb(200, 100) .'">'
  // Generate thumbnail containing (out-zooming) the image
  echo '<img src="'. $p->file('cover')->thumb(200, 100, true) .'">'

  // You can get information about the files:
  $file = $p->file('cover');
  $file->getWidth();
  $file->getHeight();
  $file->getAverageColor();
```


### Accessing folders
In On Page you can create field folders, and mark them as "default"
for any element you have.
You can easily access the folder name and the folder fields just like in the following example:
```php
// Not all items have a default folder set, so be careful!
if ($folder = $prod->getDefaultFolder()) {
    echo "Default folder: ";
    // show the translated folder label
    echo op_label($folder);
    echo "<br>";

    // Get the fields in this folder
    foreach ($folder->fields as $field) {
      // show the translated field label
        echo op_label($field);
        echo ": ";

        // use the field name to access the item values
        echo $prod->val($field->name);
        echo "<br>";
    }
    // echo "<hr>";
}
```

### File import settings
Files and thumbnails will not be downloaded during the import process, instead they are downloaded and stored when you reference them.
So, when you do something like `$prod->file('main_image')->link()` or `$prod->file('main_image')->thumb(200)` for the first time, the file will be downloaded in the plugin folder, so the page may take a while to load. Later calls will be instantaneous because the file has already been downloaded.

While this is very handful, original files can be very heavy to store on your server.
You can change the behaviour and use On Page as a CDN, so the `->link()` function will return the url from the On Page servers directly, without storing anything on your server:
```php
define('OP_DISABLE_ORIGINAL_FILE_IMPORT', true);
```

Note: in this scenario, `$file->thumb(...)` will still ask On Page to generate the thumbnail, and the thumbnail will be saved in the plugin folder.

### CDN support
On Page supports uploading files to external CDNs.
You can get the image url from the CDN simply calling `$p->file('info_file')->cdn()`.
If you use multiple CDN, you can specify the CDN name like so: `$p->file('info_file')->link('my_custom_cdn')`.

### Thumbnail settings
Thumbnails will by default use the original file format,
but you can force png, jpg, or webp using the `OP_THUMBNAIL_FORMAT` constant, like so:
```php
define('OP_THUMBNAIL_FORMAT', 'webp');
```

### 
You can use the `pluckField` method to get the field from the query.
```php
$prods = Op\Product::pluckField('name'); // ['Product A', 'Product B', 'Product C']
```

__NOTE:__ All the above functions will return the value __as is__, so if the name contains special characters, they will __not__ be returned as HTML entities (`&` becomes `&amp;`). You have the responsibility to escape the output with functions such as `htmlentities` or the shorthand `op_e($string)`.

__NOTE:__ All the above functions will return an array of values if the field is set to multiple.


## Filtering data
You can use all the eloquent methods to filter your data, but because the fields are stored inside the meta table, we provide some helper functions as follow:
```php
// Get the first chapter named "Boats"
$prods = Op\Chapter::whereField('name', 'Boats')->first()
// Get all the elements longer than 10cm
$prods = Op\Chapter::whereField('length', '>', 10)->get()
// Get elements using in operator
$prods = Op\Chapter::whereFieldIn('name', ['Boats', 'Spacecrafts'])->get()
// Full text search in all the fields (will search for %boa% in all fields)
$prods = Op\Chapter::search('boa')->get()
// Full text search only in some attributes
$prods = Op\Chapter::search('boa', ['name', 'description'])->get()
// Filter by related items (get products that have a color in the category "Dark colors")
$prods = Op\Product::whereField('colors.category.name', 'Dark colors')->get();

// Filter by On Page ID
$prods = Op\Product::whereField('_id', 1872378)->get();

// Filter by Wordpress ID
$prods = Op\Product::whereField('_wp_id', 6123)->get();

// Join filters using OR
$prods = Op\Product::where(function($q) {
  $q->whereField('length', '>', 10);
  $q->orWhereField('height', '>', 10);
})->get();

// Use deep where to customize the final query
$prods = Op\Product::deepWhere('colors.category', function($q) {
  $q->whereField('name', 'Dark colors');
})->get();
```

## Relations
You can easily access related elements using the relation name
```php
// Get all the products for the given category
$products = $category->products;
// Query all the products
$products = $category->products()->search('MK1')->get();
```


## Eager loading
All the query seen so far automatically preload all the meta attributes, to reduce the amount of stress on the database.

```php
// the following line runs two queries, one for the products on wp_posts, the second to fetch all the metadata on wp_post_meta
$prods = Op\Product::all();
```


### Relations
The related elements are not preloaded, so the following code will result in 2 + N*2 queries where N is the number of products.
```php
$prods = Op\Product::all();
foreach ($prods as $p) {
  foreach ($p->colors as $p) {
    echo $p->val('name')."<br>";
  }
}
```
To reduce the number of queries, you can easily preload the related elements (this only produces 2 or 4 queries - depending on whether the relation is `post->post`/`term->term`, or `post->term`/`term->post`):
```php
$prods = Op\Product::with('colors')->get();
foreach ($prods as $p) {
  foreach ($p->colors as $p) {
    echo $p->val('name')."<br>";
  }
}
```

### Schema - Resources & Fields
The schema is the structure of the data, it is composed by an array of resources, which in turn contain an array of fields.

```php
// Iterate all available resources and fields
foreach (op_schema()->resources as $res)
  echo $res->name;
  echo op_label($res); // resource label in current language
  echo op_label($res, 'it'); // resource label in custom language

  // Iterate resource fields
  foreach ($res->fields as $field) {
    echo $field->name;
    echo $field->type; // string | text | int | real | file | image | ...
    echo $field->unit; // cm | kg | W | ...
    echo op_label($field); // field label in current language
    echo op_label($field, 'it'); // field label in custom language
  }
}

// Find resource by name
$res = op_schema()->name_to_res['colors'];

// Find field by name
$field = $res->name_to_field['description'];
```


# Templating
You can use the normal woocommerce templating system as you're used to.
The only thing you need is to obtain an instance of the eloquent model.
You can easily do so by using the following functions:
```php
// To retrieve the model from a term
op_category('slug', 'the-category-slug'); // will return an instance to Op\MyCategory

// To retrieve the model from a post
op_product('ID', 123); // will return an instance to Op\MyProduct

// Notice you can use any column name as the first argument
$cat = op_category('term_id', 123);
$cat->getResource()->name; // 'my_category'
$cat->val('name'); // 'The Category Name'
$cat->products; // A collection of products
```


# Hooks

## Import completed
A hook called whenever an import has completed, you can use it to regenerate the cache of the website.

```php
add_action('op_import_completed', function() {
  clear_my_caches();
  notify_admin();
});
```

## Changing the slug generation function
By default, the slug is generated converting the string to ASCII
keeping accented letters (es. `à` becomes `a`) and replacing non alphanumeric
characters with a dash `-`.
```php
add_action('op_gen_slug', function($item) {
    
    if ($item instanceof \Op\Product) {
        return "{$item->val('name')}-{$item->val('titolo')}";
    } else if($item instanceof \Op\Category) {
        return "{$item->val('description')}";
    } else if($item instanceof \Op\Color) {
        return "{$item->val('name')}";
    } else {
      // Generic handler
      return "{$item->val('nome')}";
    }
});

```



## Legacy way to specify import method (do not use)
The following hook will import only the resource 'shoes' and 't_shirts' as product,
and any other resource will be imported as a category.

```php
add_filter('on_page_product_resources', function() {
  return ['shoes', 't_shirts']; // list of resource names (not labels)
});
```


## Importing relations
__NOTE:__ Only works for category-subcategory

Relations will not be imported by default.
You can specify, for each resource, wich relation to set the term parent.


```php
add_action('op_import_relations', function() {
    return [
        // On Page resource name => // On Page parent relation name
        'prodotti' => 'categorie',
    ];
});
```

# Advanced language options


### Setting up fallback languages
By default, if no value is found in the current project, the system will fallback to the main project language.
If the current language is country-specific (e.g. de_AU) then the system will also try the country-free alternative "de".
If you need to use alternative languages when translations are missing, you can setup your own fallback languages:
```php
// When Norwegian is empty, try to find a value in the English or Italian language
op_set_fallback_lang('no', ['en', 'it']);
```

### Mapping WPML locale to On Page language
You can map WPML locales to a specific On Page language just like the following:
```php
// a map of WPML_LOCALE => OP_LANGUAGE to use
set_op_locale_to_lang([
  'en' => 'en_gb',
]);
```



# Example templates
## Shop Page
```php
<?php
foreach (Op\Category::all() as $cat): ?>
  <a href="<?= $cat->link() ?>">
    <?= $cat->val('name') ?>
  </a>
<?php endforeach; ?>

```

## Category Page
```php
$category = op_category('slug', $term->slug);
<h1><?= $category->val('name') ?></h1>
<?php
foreach ($category->products as $prod): ?>
  <a href="<?= $prod->link() ?>">
    <?= $prod->val('name') ?>
  </a>
<?php endforeach; ?>

<?php get_footer(); ?>
```

## Product Page
You should understand the way it works by now. Simply use the `->link()` method to get the link to the item.

# Automate imports with APIs
First of all, define a random token in your theme functions.php:
```php
define('OP_API_TOKEN', '0T780347N89YGA78EYN');
```

You can do a POST call to your website passing the following parameters:

__Command__:  
`op-api`: import  
__Your secret token__:  
`op-token`: 0T780347N89YGA78EYN  
__Generate a new snapshot before importing__:  
`regen-snapshot`: true/false  
__Import even if there are no updates from On Page__:  
`force`: true/false  
__Regenerate all slugs__:  
`force-slug-regen`: true/false  

If you are using cron or similar, you can use the CURL command:
`curl -X POST 'https://yourwebsite/?op-api=import&op-token=0T780347N89YGA78EYN&regen-snapshot=true&force=false'`



# Automate imports with wp-cli
You can easily automate the snapshot through wp-cli using the command `wp onpage import`.

By default, __the import will exit instantly if there is no new data to be imported__.

This is useful because you can run this command in a cron every minute, and it will only actually import your data when a new snapshot is available.

If you want to ovverride this behaviour and import data anyway, you can add use the `wp onpage import --force` command, wich will re-import all your data.

You can also force the regeneration of a new snapshot before importing, which would be `wp onpage import --regen-snapshot`.

If you are on __development__ and want the existing slugs to be updated as well, you can add the `--force-slug-regen` flag to the command.

## Other wp-cli commands:
```bash
wp onpage reset # Delete all the products and categories in WooCommerce
wp onpage listmedia # List the files imported in WooCommerce
```

# Important Notes
- Functions and other plugin methods that are not documented here are subject to change and therefore should not be used.
- The plugin will modify the posts and terms table and add some custom columns
