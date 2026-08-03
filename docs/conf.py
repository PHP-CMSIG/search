# Configuration file for the Sphinx documentation builder.
#
# For the full list of built-in configuration values, see the documentation:
# https://www.sphinx-doc.org/en/master/usage/configuration.html

# -- Project information -----------------------------------------------------
# https://www.sphinx-doc.org/en/master/usage/configuration.html#project-information

project = 'SEAL'
copyright = '2023, Alexander Schranz'
author = 'Alexander Schranz'
release = '0.13'
html_title = 'SEAL Documentation'
html_short_title = 'SEAL'
html_favicon = '_static/icons/favicon.ico'
html_logo = '_static/icons/logo.svg'

html_context = {
    "display_github": True, # Integrate GitHub
    "github_user": "PHP-CMSIG", # Username
    "github_repo": "search", # Repo name
    "github_version": "0.13", # Version
    "conf_py_path": "/docs/", # Path in the checkout to the docs root
}

# -- General configuration ---------------------------------------------------
# https://www.sphinx-doc.org/en/master/usage/configuration.html#general-configuration

extensions = ['sphinx_tabs.tabs']

templates_path = ['_templates']
exclude_patterns = []

# -- Options for HTML output -------------------------------------------------
# https://www.sphinx-doc.org/en/master/usage/configuration.html#options-for-html-output

# html_theme = 'alabaster'
html_theme = "furo"

html_static_path = ['_static']
html_css_files = [
    'css/custom.css',
]

# Tabs
sphinx_tabs_disable_tab_closing = True
