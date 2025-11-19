# GitHub Actions Setup

## Initial Setup

After pushing this repository to GitHub, you'll need to:

### 1. Update Repository URLs in README.md

Replace `YOUR_USERNAME` in the badge URLs with your actual GitHub username:

```markdown
[![CI](https://github.com/YOUR_USERNAME/toon/workflows/CI/badge.svg)](https://github.com/YOUR_USERNAME/toon/actions)
[![Coverage Status](https://coveralls.io/repos/github/YOUR_USERNAME/toon/badge.svg?branch=main)](https://coveralls.io/github/YOUR_USERNAME/toon?branch=main)
```

### 2. Configure Coveralls

1. Go to https://coveralls.io/
2. Sign in with your GitHub account
3. Add your repository (toon)
4. Get your repository token
5. Add the token as a GitHub secret:
   - Go to your repository Settings → Secrets and variables → Actions
   - Click "New repository secret"
   - Name: `COVERALLS_REPO_TOKEN`
   - Value: (paste the token from Coveralls)

### 3. Verify Workflows

After the first push, check:
- GitHub Actions tab to see if workflows are running
- All three jobs should pass: Code Style, Static Analysis, Tests
- Coverage should be uploaded to Coveralls

## Workflow Details

The CI workflow runs on:
- **Push** to main, master, develop, and feature/* branches
- **Pull requests** to main, master, and develop branches

It includes three jobs:
1. **Code Style**: Checks code formatting with PHP CS Fixer
2. **Static Analysis**: Runs PHPStan level 10
3. **Tests**: Runs PHPUnit with coverage and uploads to Coveralls

## Local Testing

Before pushing, you can run the same checks locally:

```bash
# Code style
vendor/bin/php-cs-fixer fix --dry-run --diff

# Static analysis
vendor/bin/phpstan analyse

# Tests with coverage
vendor/bin/phpunit --coverage-html coverage
```

## Troubleshooting

If the coverage upload fails:
- Verify the `COVERALLS_REPO_TOKEN` secret is set correctly
- Check that the repository is enabled in Coveralls
- Ensure the branch name matches (main vs master)
