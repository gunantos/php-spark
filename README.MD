# PHP SIMPLE SPARK

<a href="https://sponsor.app-kita.net" target="_blank"><img src="https://img.shields.io/github/sponsors/gunantos?logo=gunantos&style=for-the-badge" title="Pay Coffe" /></a><br>
PHP Create Spark Serve for develop your PHP Aplication<br>

## USE

- create file ex: `spark`

```php
<?php
$config = [
  'host'=>'127.0.0.1',
  'port'=>8080,
  'path'=>'',
  'indexFiles'=> ['index.php', 'index.html'],
  'router' => []
];
$serve = new Appkita\SPARK\Serve($config); // default array empty || []
$serve->run();
```

- open commandprom

```sh
php spark
```

### INSTALATION

`composer require appkita/spark-serve`

### Lear More

- [php](https://www.php.net/manual/en)

### Chat

[Discord](https://discord.gg/bXUWCSaw)

### Sponsor

[Pay Coffe](https://sponsor.app-kita.net)
