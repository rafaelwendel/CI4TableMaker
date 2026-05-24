# TableMaker

**TableMaker** is a helper library for the **CodeIgniter 4** framework designed to extend the capabilities of the native `CodeIgniter\View\Table` class.

It simplifies the task of dynamically injecting action columns (such as "Edit", "Delete", or custom buttons) into HTML tables. It features support for **conditional action links per row** (e.g., showing a button only if the user is an Admin) and **dynamic placeholder substitution** for database columns in URLs.

---

## 🚀 Installation

Add the package to your CodeIgniter 4 project using Composer:

```bash
composer require rafaelwendel/ci4tablemaker
```

> [!NOTE]
> If you are using the class directly in your project's `app/Libraries/` folder without Composer, you can import it using the local namespace `use App\Libraries\TableMaker;`.
> If you installed the package via Composer, import it using the package namespace: `use CI4TableMaker\TableMaker;`.

---

## 🛠️ How to Use

### 1. Basic Usage

In your Controller, fetch your users' dataset (with columns `id`, `name`, and `profile`), define the action links, and send the helper instance to your View.

```php
<?php

namespace App\Controllers;

use CI4TableMaker\TableMaker; // If installed via Composer, or use App\Libraries\TableMaker; if local

class Users extends BaseController
{
    public function index(): string
    {
        // 1. Fetch user data (e.g., from a Model)
        $userModel = new \App\Models\UserModel();
        $users = $userModel->findAll(); // Array of records containing: id, name, profile

        // 2. Instantiate TableMaker passing CodeIgniter 4's Table library
        $tableMaker = new TableMaker(new \CodeIgniter\View\Table());

        // 3. Set table template and headers
        $template = [
            'table_open' => '<table class="table table-bordered table-striped">'
        ];
        $tableMaker->setTemplate($template);
        $tableMaker->setHeading('ID', 'Name', 'Profile', 'Actions');

        // 4. Set the data and the URL base for your action links
        $tableMaker->setData($users);
        $tableMaker->setUrlBase(base_url('users'));

        // 5. Add action links
        // It automatically replaces `{id}` with the "id" value from each row
        $tableMaker->addLink('edit/{id}', 'id', 'Edit', 'class="btn btn-primary btn-sm"');
        $tableMaker->addLink('delete/{id}', 'id', 'Delete', 'class="btn btn-danger btn-sm"');

        // 6. Send the instance to your View
        return view('users/index', ['table' => $tableMaker]);
    }
}
```

In your View (`app/Views/users/index.php`), simply print the object:

```php
<div class="container">
    <h1>Users List</h1>
    <?= $table ?>
</div>
```

---

### 2. Conditional Action Links

You can set conditions so an action link is only displayed if specific criteria are met. The library supports the following operators: `==`, `===`, `!=`, `>`, `<`, `>=`, `<=`.

For example, to display an "Advanced Settings" link only for users whose profile is `'Admin'`:

```php
$condition = [
    'field'    => 'profile',
    'operator' => '==',
    'value'    => 'Admin'
];

$tableMaker->addLink(
    'settings/{id}', 
    'id', 
    'Advanced Settings', 
    'class="btn btn-warning btn-sm"', 
    $condition
);
```

---

### 3. Dynamic URL Placeholders

The library replaces wildcards inside your URL paths using any column key available in your dataset.

```php
// Supports replacing multiple properties found in the row data
$tableMaker->addLink(
    'departments/{profile}/users/{id}/details', 
    null, 
    'View Details', 
    'class="btn btn-info btn-sm"'
);
```

---

### 4. Selecting and Ordering Columns

Using the `setColumns()` method, you can specify exactly which columns of your dataset should be displayed in the final HTML table and define their **exact order**.

```php
// Your data may contain: id, name, profile, and password_hash.
// To only display Name and Profile in this exact order:
$tableMaker->setColumns(['name', 'profile']);
```

---

## 📖 Methods Reference

### `setHeading(...$heading): void`
Sets the table headers. Accepts an array or discrete arguments.

### `setFooting(...$footing): void`
Sets the table footer. Accepts an array or discrete arguments.

### `setTemplate(array $template): void`
Sets the HTML table template using CodeIgniter 4's Table library array layout.

### `setData(iterable $data): void`
Injects the dataset (array of arrays, array of objects, or database query results) that will populate the table.

### `setUrlBase(string $urlBase): void`
Sets the prefix URL base for all action link paths.

### `setColumns(array $columns): void`
Specifies which columns should be displayed in the HTML table and sets their render order.

### `setSeparator(string $separator): void`
Defines the separator string inserted between action links. Default: `&nbsp;`.

### `addLink(string $path, ?string $param, string $title, array|string $attr = '', ?array $condition = null): void`
Adds an action link.
- **`$path`**: The link path (e.g., `'edit/{id}'`).
- **`$param`**: Maintained for legacy compatibility. Key whose value replaces `{param}` in the URL.
- **`$title`**: The link label or button HTML.
- **`$attr`**: Extra link attributes like `class`, `id`, or `target` (accepts string or array).
- **`$condition`**: Conditional array configuration (`['field' => ..., 'operator' => ..., 'value' => ...]`).

### `display(bool $returnData = false, string $indexTitle = 'actions'): array|string`
Generates and returns the table as an HTML string by default, or returns the processed dataset as an array if `$returnData` is `true`.

---

## 📄 License

This project is licensed under the MIT License.
