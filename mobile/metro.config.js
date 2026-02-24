// metro.config.js
const { getDefaultConfig } = require('expo/metro-config');

/** @type {import('expo/metro-config').MetroConfig} */
const config = getDefaultConfig(__dirname);

// Add web support
config.resolver.resolverMainFields = [
  'browser',
  'main',
  ...config.resolver.resolverMainFields,
];

// Resolve .web.js files for web platform
config.resolver.sourceExts = [
  ...config.resolver.sourceExts,
  'web.js',
  'web.ts',
  'web.tsx',
];

// Add asset extensions
config.resolver.assetExts = [
  ...config.resolver.assetExts,
  'db', // For SQLite databases
  'sqlite', // For SQLite databases
];

// Watch all files in the app directory
config.watchFolders = [__dirname];

module.exports = config;