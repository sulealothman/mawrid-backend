# Changelog

All notable changes to this project will be documented in this file.

## [0.1.0] - 2026-03-30

### Added
- Added `AI_WS_URL`, `AI_WS_TOKEN_SECRET`, and `AI_WS_TOKEN_TTL` to backend environment configuration and example templates.
- Added parent message and status fields to chat messages.
- Added support for expanded chat message role values.

### Changed
- Refactored the chat architecture so the frontend connects directly to the AI service for chat over WebSocket.
- Limited the backend role in chat to authentication and token issuance.
- Removed backend chat message relaying between the frontend and AI service.