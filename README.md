<h1 align="center">Project template</h1>

Installation & usage
--------------------
1. Create your project
```bash
$ composer create-project elodgy/project-template my-project
```

2. Modify composer.json to fit with your project

```json
{
  "name": "elodgy/my-project",
  "description": "My project",
  "type": "library",
  "autoload": {
      "Elodgy\\MyProject\\": "src/"
  },
  "autoload-dev": {
    "Elodgy\\MyProject\\Tests\\": "tests/"
  }
}
```

3. Initialize git
```bash
$ git init
```

4. Enjoy!!
