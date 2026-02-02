# Augment AI Agent Directory

This directory contains Augment AI agent-related information to bring additional context from the Predis project and provide reusable commands for common development tasks.

## Purpose

The `.augment` directory serves as a knowledge base and command repository for the Augment AI agent, enabling:

- **Contextual Understanding**: Provides specifications and documentation that help the AI agent understand Redis commands and Predis implementation patterns
- **Reusable Commands**: Offers pre-defined workflows for common development tasks
- **Consistency**: Ensures that AI-assisted development follows project conventions and best practices
- **Efficiency**: Reduces repetitive explanations by storing reusable instructions and templates

## Directory Structure

```
.augment/
├── command-specification-template.md   # Template for Redis command specifications
├── commands/                           # Reusable AI agent commands
└── reference/                          # Reference documentation and best practices
```

### Components

- **`command-specification-template.md`**: Template for creating specifications for new or existing Redis commands. Use this as a starting point when documenting command requirements.

- **`commands/`**: Contains reusable command workflows that can be executed via the `auggie` CLI tool. Each command file defines a structured workflow for specific development tasks.

- **`reference/`**: Additional documentation, best practices, and guidelines for working with the Predis codebase.

## Using Commands with `auggie` CLI

The `auggie` CLI tool allows you to execute predefined commands that guide the Augment AI agent through complex workflows.

### Basic Commands

```bash
# List all available commands
auggie command list

# Execute a specific command
auggie command <command-name> [arguments]
```

Commands are automatically discovered from the `.augment/commands/` directory.

### How It Works

When you execute a command with `auggie command <command-name>`, the AI agent:

1. **Reads the command file** - Understands the workflow and requirements from `.augment/commands/<command-name>.md`
2. **Processes arguments** - Uses any provided arguments (e.g., specification files)
3. **Executes the workflow** - Follows the structured steps defined in the command
4. **Verifies implementation** - Checks syntax, types, and conventions
5. **Reports results** - Provides a summary of completed tasks

### Example Usage

```bash
# List available commands
auggie command list

# Execute a command with arguments
auggie command add-new-command path/to/specification.md
```

Refer to individual command files in the `commands/` directory for specific usage instructions and argument requirements.

## Command Specification Template

The `command-specification-template.md` file serves as a template for creating specifications for new or existing Redis commands. It should be used whenever you need to document a Redis command for implementation or updates.

### Template Structure

The template includes the following sections:

- **Supported version**: Minimum Redis version required
- **Command description**: Detailed explanation of the command functionality
- **Command API**: Command syntax with all arguments and options
- **Redis-CLI examples**: Real-world usage examples
- **Test plan**: Integration test scenarios and requirements

## Best Practices

- **Be specific**: Include all arguments, options, and their constraints in specifications
- **Provide examples**: Real Redis-CLI examples help understand expected behavior
- **Define test scenarios**: Clear test plans ensure comprehensive coverage
- **Review AI output**: Always review generated code for correctness and style
- **Follow conventions**: The AI agent follows project patterns, but human review is essential
