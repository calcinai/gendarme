# Gendarme

A tool for creating PHP data structures from [JSON Schemas](http://json-schema.org).

## Setup

via composer
```
composer create-project calcinai/gendarme -s dev
```

## Usage

From the project root:
```
./bin/gendarme generate --namespace [TARGET_NAMESPACE] --root-class [ROOT_CLASS]  [SCHEMA_FILE].json [OUTPUT_DIR]
```

## Output

All models will be PSR-4 compliant based on the arguments given at runtime.  A typical output folder structure will be as follows:

```
- OUTPUT_DIR
  - BaseSchema.php (the class all models extend)
  - RootClass.php (the --root-class argument)
  - Definitions
    - ...
    - ...
```


This project was started to generate the schema files for [Strut](https://github.com/calcinai/strut), a Swagger/OAPI manipulation library.  Although a very complex example, it shows the basic output structure that will be generated.

The generated models will include type-hinting to other models where possible, and non-hintable objects will be put in the doc blocks. At this point, many, but not all schema keywords are parsed.  As more desired functionality is identified, more can be implemented.

