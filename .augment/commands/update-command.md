---
description: Update existing Redis command
argument-hint: [path-to-specification]
---

# Execute: Update existing Redis command

## Plan to Execute

Read specification file: `$ARGUMENTS`

## Execution Instructions

### 1. Read and Understand

- Read the ENTIRE specification carefully
- Identify from the specification title the command name and check if it exists
- Identify if there's a need to change the command API
- Check relevant Redis-Cli examples, if provided
- Review the Test Plan

### 2. Execute Tasks in Order

#### a. Navigate to the task
- Identify the files and action required
- Read existing related files if modifying

#### b. Update command API
- Skip this step if there's no need to change the command API
- Update command API in `src/Command/Redis` directory
- Update command API in `src/ClientInterface.php` and `src/ClientContextInterface.php`
- Ask permission if changes are backward incompatible

#### c. Verify as you go
- After each file change, check syntax
- Ensure imports are correct
- Verify types are properly defined

### 3. Implement Testing Plan

After completing implementation tasks:

- Check supported version for changes in specification, ensure integration tests are skipped for older Redis versions.
- Update existing tests if specification has changed
- Implement new test cases if mentioned
- Follow the testing approach outlined
- Ensure tests cover edge cases

### 4. Final Verification

Before completing:

- ✅ All tasks from plan completed
- ✅ All tests created and passing
- ✅ Code follows project conventions
- ✅ Documentation added/updated as needed

## Output Report

Provide summary:

### Completed Tasks
- List of all tasks completed
- Files created (with paths)
- Files modified (with paths)

### Tests Added
- Test files created
- Test cases implemented
- Test results

