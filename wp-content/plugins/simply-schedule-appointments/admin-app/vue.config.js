const { defineConfig } = require('@vue/cli-service')
module.exports = defineConfig({
  transpileDependencies: true,
  filenameHashing: false,
  devServer: {
    proxy: {
      '/wp-': {
          target: 'http://ssa.localdev',
          changeOrigin: true
      },
      '/getApi.php': {
          target: 'http://ssa.localdev/wp-content/plugins/simply-schedule-appointments',
          changeOrigin: true
      }
    }
  },

  assetsDir: 'static'
})
