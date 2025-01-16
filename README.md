# generator
Abandoned! Please use https://github.com/boneframework/generator
Generate doctrine entities, services, repositories, and forms.
## installation
Composer etc.
## package generation
```
$ vendor/bin/generator entity
Entity and Service Generator

Enter the base namespace: Del\Animal
Enter the name of the entity: Unicorn

Create a field? (Y/n)
Enter the name of the field: name
What type of data does it hold? 
  [0] date
  [1] datetime
  [2] decimal
  [3] double
  [4] float
  [5] int
  [6] varchar
  [7] bool
 > 6
What length is the field? 30
Is the field nullable?  (Y/n)

Create another field? (Y/n)
Enter the name of the field: dob
What type of data does it hold? 
  [0] date
  [1] datetime
  [2] decimal
  [3] double
  [4] float
  [5] int
  [6] varchar
  [7] bool
 > 0
Is the field nullable?  (Y/n)

Create another field? (Y/n)
Enter the name of the field: isHungry
What type of data does it hold? 
  [0] date
  [1] datetime
  [2] decimal
  [3] double
  [4] float
  [5] int
  [6] varchar
  [7] bool
 > 7
Is the field nullable?  (Y/n)

Create another field? (Y/n)n

Successfully generated in build/5cd362ce88907.

```
### run db migrations
- copy `migrant-cfg.php.dist` removing the `.dist` extension, then edit with your db details.
- create a migrations folder
- move the contents of the build folder into `src` of your project or package
- edit Package class with the entity path, usually `src/Entity` or `vendor/vendorname/package/src/Entity`
```
vendor/bin/migrant diff
vendor/bin/migrant migrate
```
## usage
If you are using `Del\Common`, create a Container and add the package. It will be available under the key `service.YourEntityName`.

If not then create a Doctrine Entity Manager and pass it to the service constructor.
