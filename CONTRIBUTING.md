# Contributing to StatGuard

First off, thank you for considering contributing to StatGuard! It's people like you who make the open-source community such an amazing place to learn, inspire, and create.

## 🚀 How Can I Contribute?

### Reporting Bugs
If you find a bug, please create a new **Issue**. Be sure to include:

* A clear and descriptive title.
* Steps to reproduce the problem.
* What you expected to happen and what actually happened.
* Your PHP version and environment details.

### Suggesting Enhancements
We are always open to new statistical metrics or export formats! Please open an **Issue** first to discuss the idea before starting to code.

### Pull Requests (PRs)
1.  **Fork** the repository and create your branch from `main`.
2.  If you've added code that should be tested, **add tests**.
3.  Ensure the test suite passes (run `./vendor/bin/phpunit`).
4.  Follow **PSR-12** coding standards.
5.  Write clear and descriptive commit messages.
6.  Open a **Pull Request**!

## 🛠 Development Setup
To get started with the codebase:

```bash
# Clone your fork
git clone [https://github.com/cjuol/stat-guard.git](https://github.com/cjuol/stat-guard.git)

# Install dependencies
composer install

# Run tests to make sure everything is working
./vendor/bin/phpunit
```
## 📜 Style Guide

* **Namespaces**: Always follow the `Cjuol\StatGuard` structure.
* **Strict Typing**: All files must include `declare(strict_types=1);`.
* **Documentation**: Update the `README.md` if you change any public API or method names.

## ⚖️ Code of Conduct

By participating in this project, you agree to abide by our [Code of Conduct](./CODE_OF_CONDUCT.md).
