# Contributing

Your contributions are highly appreciated, and they will be duly recognized.

Before you proceed to create an issue or a pull request, please take a moment to familiarize yourself with our contribution guide.

## Etiquette

This project thrives on the spirit of open-source collaboration. Our maintainers dedicate their precious time to creating and upholding the source code, and they share it with the hope that it will benefit fellow developers. Let's ensure they don't bear the brunt of abuse or anger for their hard work.

When raising issues or submitting pull requests, let's maintain a considerate and respectful tone. Our goal is to exemplify that developers are a courteous and collaborative community.

The maintainers have the responsibility to evaluate the quality and compatibility of all contributions with the project. Every developer brings unique skills, strengths, and perspectives to the table. Please respect their decisions, even if your submission isn't integrated.

## Relevance

Before proposing or submitting new features, consider whether they are genuinely beneficial to the broader user base. Open-source projects serve a diverse group of developers with varying needs. It's important to assess whether your feature is likely to be widely useful.

## Procedure

### Preliminary Steps Before Filing an Issue

- Try to replicate the problem to ensure it's not an isolated occurrence.
- Verify if your feature suggestion has already been addressed within the project.
- Review the pull requests to make sure a solution for the bug isn't already underway.
- Check the pull requests to confirm that the feature isn't already under development.

### Preparing Your Pull Request

- Examine the codebase to prevent duplication of your proposed feature.
- Check the pull requests to verify that another contributor hasn't already submitted the same feature or fix.

## How to Contribute

### Fork the Repository

1. **Fork the Repository**:
   - Go to the [data-block repository](https://github.com/Hi-Folks/data-block) on GitHub.
   - Click the "Fork" button at the top right of the page. This creates a copy of the repository under your GitHub account.

2. **Clone the Forked Repository**:
   - Open your terminal and clone the forked repository to your local machine.
   ```sh
   git clone https://github.com/YOUR-USERNAME/data-block.git
   ```
   - Navigate to the project directory:
   ```sh
   cd data-block
   ```

### Create a new branch

3. **Create a Branch locally**
   - It's important to create a new branch for your work to keep your changes organized and separate from the main codebase
   ```sh
   git checkout -b your-feature-branch
   ```
   - Replace the `your-feature-branch` with a meaningful name that describes the feature you're adding or the bug you're fixing. You can use also folders like `feat/101-add-new-filter-operator`. My suggestion is to use the `feat/` folder for new features and to use the `fix/` folder for bug fixing. In the slug of the name of the branch, I suggest you set the number of the issue you want to solve as a prefix. For example, the `feat/101-add-new-filter-operator` name is a new feature implementation requested in issue number 101.

### Make your changes locally

4. **Make Your Changes**
   - Implement your changes in the new branch you created. Ensure your code follows the project's style guidelines and passes any tests.

5. **Commit Your Changes**
   - After making your changes, commit them with a descriptive commit message
   ```sh
   git add .
   git commit -m "Description of the feature or fix"
   ```
### Push and Open a Pull Request

6. **Push Your Changes**
   - Push your changes to your forked repository on GitHub
   ```sh
   git push origin your-feature-branch
   ```

7. **Open a Pull Request**
   - Go to the original [data-block repository](https://github.com/Hi-Folks/data-block) on GitHub
   - Click the "Compare & pull request" button next to your recently pushed branch
   - Fill out the pull request template with relevant information about the changes you made

8. **Address Feedback**
   - If the project maintainers request any changes or provide feedback, update your branch accordingly and push the changes to your fork. The pull request will automatically update.


## Suggestions for opening a Pull Request

To maintain coding consistency, we adhere to the PER coding standard and use PHPStan for static code analysis. You can utilize the following command:

```bash
composer all
```
This command encompasses:

- PER Coding Standard checks employing Pint/PHP_CodeSniffer.
- PHPStan analysis at level 9.
- Execution of all tests from the `./tests/*` directory using PestPHP.

We recommend running `composer all` before committing and creating a pull request.

When working on a pull request, it is advisable to create a new branch that originates from the main branch. This branch can serve as the target branch when you submit your pull request to the original repository.

For a high-quality pull request, please ensure that you:

- Include tests as part of your patch. We cannot accept submissions lacking tests.
- Document changes in behavior, keeping the README.md and other pertinent documentation up-to-date.
- Respect our release cycle. We follow SemVer v2.0.0, and we cannot afford to randomly break public APIs.
- Stick to one pull request per feature. Multiple changes should be presented through separate pull requests.
- Provide a cohesive history. Each individual *commit* within your pull request should serve a meaningful purpose. If you have made several intermediary commits during development, please consolidate them before submission.

Happy coding! 🚀
