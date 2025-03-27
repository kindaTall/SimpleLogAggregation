import setuptools
import os

# Read the contents of README.md
this_directory = os.path.abspath(os.path.dirname(__file__))
with open(os.path.join(this_directory, 'README.md'), encoding='utf-8') as f:
    long_description = f.read()

setuptools.setup(
    name="log-aggregator-handler",
    version="0.1.0",
    author="Cline", # Assuming Cline is the author, adjust if needed
    author_email="cline@example.com", # Placeholder email
    description="A Python logging handler for sending logs to a centralized aggregation API.",
    long_description=long_description,
    long_description_content_type="text/markdown",
    url="https://github.com/yourusername/log-aggregator-handler", # Placeholder URL
    package_dir={'': 'src'},
    packages=setuptools.find_packages(where='src'),
    classifiers=[
        "Programming Language :: Python :: 3",
        "License :: OSI Approved :: MIT License", # Assuming MIT, adjust if needed
        "Operating System :: OS Independent",
        "Development Status :: 3 - Alpha", # Initial development stage
        "Intended Audience :: Developers",
        "Topic :: System :: Logging",
    ],
    python_requires='>=3.7', # Specify minimum Python version
    install_requires=[
        "requests>=2.20.0", # Dependency for making HTTP requests
        "python-dotenv>=1.0.0",
    ],
    extras_require={
        "dev": [ # Optional dependencies for development/testing
            "pytest>=6.0",
            "requests-mock>=1.8",
        ],
    },
    project_urls={ # Optional: Links for documentation, issue tracker etc.
        'Bug Reports': 'https://github.com/yourusername/log-aggregator-handler/issues',
        'Source': 'https://github.com/yourusername/log-aggregator-handler/',
    },
)
