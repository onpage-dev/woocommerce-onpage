# Onpage Woocommerce Docs

# Intro
This plugin is used to import a project snapshot into your woocommerce website. It uses the wordpress tables so you can use it the way you are used to. __All field data is saved into the object meta table.__
You can create a project snapshot (and the corresponding token) using the *App Mobile* feature in OnPage.

# Handling data
When you import your snapshots, the plugin will generate [Eloquent Models](https://laravel.com/docs/7.x/eloquent) for your data, in the plugin directory `db-models/` these models are updated every time you import your data.

You can view the models generated for your project in the plugin import page. For each model, you'll find the list of relations and fields imported.

## Selecting data
For instance, assuming you want to select all your products (codenamed `Product`), you can run:
```php
$prods = Op\Product::all();
foreach ($prods as $prod) {
  // get the name in the current language
  echo $prod->val('name')."<br>\n";
  // get the name in a custom language
  echo $prod->val('name', 'zh')."<br>\n";
  // Gets a file name
  echo $p->file('info_file')->name; // e.g. MK100.pdf
  // Gets the original image/file url
  echo '<a href="'. $p->file('info_file')->link() .'">Download PDF</a>'
  // Resize image to width 100 and automatic height (generated in run-time and cached)
  echo '<img src="'. $p->file('cover')->thumb(100) .'">'
  // Generate thumbnail cropping (zooming) the image
  echo '<img src="'. $p->file('cover')->thumb(200, 100) .'">'
  // Generate thumbnail containing (out-zooming) the image
  echo '<img src="'. $p->file('cover')->thumb(200, 100, true) .'">'
}
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
// Full text search in all the fields (will search for %boa% in all fields)
$prods = Op\Chapter::search('boa')->get()
// Full text search only in some attributes
$prods = Op\Chapter::search('boa', ['name', 'description'])->get()
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


# Templating
You can use the normal woocommerce templating system as you're used to.
The only thing you need is to obtain an instance of the eloquent model.
You can easily do so by using the following functions:
```php
// To retrieve the model from a term
op_category('slug', 'the-category-slug'); // will return an instance to Op\MyCategory

// To retrieve the model from a post
op_post('ID', 123); // will return an instance to Op\MyProduct

// Notice you can use any column name as the first argument
$cat = op_category('term_id', 123);
$cat->getResource()->name; // 'my_category'
$cat->val('name'); // 'The Category Name'
$cat->products; // A collection of products
```



# Routing
This plugins also implements an optional router with link generation. To use it set up the shop base url (e.g. `shop/`) in the plugin settings, and add the following to your theme:

```php
// This will handle the shop home (e.g. /shop/)
op_page('/', function() {
  include __DIR__.'/shop-home.php';
});
op_page('/Category/', function($category) {
  include __DIR__.'/shop-category.php';
});
op_page('/Category/products', function($product) {
  include __DIR__.'/shop-product.php';
});
```

Then create the related files as follows:
## Shop Page
```php
<?php
defined( 'ABSPATH' ) || exit;
get_header();

foreach (Op\Category::all() as $cat): ?>
  <a href="<?= $cat->link() ?>">
    <?= $cat->val('name') ?>
  </a>
<?php endforeach; ?>

<?php get_footer(); ?>
```

## Category Page
```php
<?php
defined( 'ABSPATH' ) || exit;
get_header();
?>
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

__NOTE:__ the `->link()` method will fail with an error if no route is present for the intended resource.

## Advanced routing
Sometimes you want to reduce the number of levels required to access the product page.
Suppose you have the following structure:
- Chapter
- Section
- Product
- Article


And we want the following 3 pages:
- Home url: `/shop/`
- Section url: `/shop/section-slug/`
- Article url: `/shop/section-slug/article-slug/`

```php
op_page('/', function() {
  include __DIR__.'/home.php';
});
op_page('/Section/', function(Op\Section $section) {
  include __DIR__.'/section.php';
});

// we can shortcircuit the products using the . to separate the relations names
op_page('/Section/products.articles', function(Op\Article $article) {
  include __DIR__.'/article.php';
});
```


__NOTE:__ let's breakdown that `/Section/products.articles`:
- `Section` is the name of the Eloquent Model (Op\Section must exist)
- `products` is the relation starting from Section (e.g. the one you use when you do `$section->products`)
- `articles` is the relation starting from the Product resource (e.g. $product->articles)

__NOTE:__ In this last example, calling `$product->link()` will throw an exception because there is no route to handle a `Op\Product`
