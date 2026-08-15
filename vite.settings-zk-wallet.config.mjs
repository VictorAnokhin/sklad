export default {
  publicDir: false,
  build: {
    emptyOutDir: false,
    lib: {
      entry: 'resources/js/settings-zk-wallet.js',
      formats: ['es'],
      fileName: () => 'settings-zk-wallet.js',
    },
    outDir: 'public/js',
    rollupOptions: {
      output: {
        inlineDynamicImports: true,
      },
    },
  },
};
