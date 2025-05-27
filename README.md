# SISFO SARPRAS (Sistem Informasi Sarana dan Prasarana Sekolah)
API unruk warehouse management system menggunakan laravel dan juga sanctum

# Features
- User, Warehouse, Category, Item, ItemUnit, Borrow request, Return request management system
- Authentication & Authorization
- Role-Based Access Control (User, Admin)
- Item unit QR code generator
- Log activity
- Import & export to excel

# How To Run
### Set the ENV
```sh
cp .env.example .env
```
### Install all packages
```sh
composer i
```
### Migrate
```sh
php artisan migrate
```
### Generate key
```sh
php artisan key:generate
```
### Seeding
```sh
php artisan db:seed
```
### Run!
```sh
php artisan serve
```
