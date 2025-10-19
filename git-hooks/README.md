# Git Hooks

This directory contains git hooks to enforce repository standards.

## Installation

To install the hooks, run:

```bash
cp git-hooks/commit-msg .git/hooks/commit-msg
chmod +x .git/hooks/commit-msg
```

## Available Hooks

### commit-msg

Prevents commits that contain Claude Code mentions, including:
- "Claude Code" references
- "🤖 Generated with" messages
- "Co-Authored-By: Claude" trailers

This ensures all commits are properly attributed to human contributors only.
