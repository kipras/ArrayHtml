# ArrayHtml

PHP class for pretty-printing nested arrays/objects using HTML, CSS and JavaScript, providing buttons
to expand/collapse each level.

Supports printing recursive objects, i.e. objects that have children that point back to the parent object.

If a printed object has a `__toArray()` method - that method will be called and any additional data
returned by it will be merged together with the data returned by `get_object_vars()` and pretty-printed.

Example screenshot:  
![ArrayHtml screenshot](http://www.kipras.com/kipras_libs/ArrayHtml.png)

## Usage

- **ArrayHtml::show($data)**  
Pretty-prints $data


- **ArrayHtml::get($data)**  
Returns the pretty-printed HTML of $data

## Requirements

* PHP >= 5.3 (uses static:: keyword)
