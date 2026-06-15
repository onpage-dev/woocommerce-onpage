<script src="https://cdnjs.cloudflare.com/ajax/libs/vue/2.6.11/vue.min.js" charset="utf-8"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/axios/0.19.2/axios.min.js" charset="utf-8"></script>

<style>
  #op-api-app h1 {
    color: #56bd48;
  }

  #op-api-app .op-card {
    background: #fff;
    padding: 1rem 1.5rem;
    border-left: 2px solid #56bd48;
    box-shadow: 0 0 8px -2px rgba(0, 0, 0, .3);
    margin: 1rem 0;
    max-width: 900px;
  }

  #op-api-app .button {
    background: #56bd48 !important;
    border: 1px solid #46ad38 !important;
    color: #fff !important;
    margin-right: 0.5rem;
    margin-top: 0.5rem;
  }

  #op-api-app .button.button-danger {
    background: #BF616A !important;
    border: 1px solid #BF616A !important;
  }

  #op-api-app .button:disabled {
    opacity: .5;
  }

  #op-api-app .op-notice {
    padding: 0.75rem 1rem;
    margin: 1rem 0;
    border-left: 4px solid #dba617;
    background: #fcf9e8;
  }

  #op-api-app .op-notice-info {
    border-left-color: #72aee6;
    background: #f0f6fc;
  }

  #op-api-app .op-token-display {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex-wrap: wrap;
    margin: 1rem 0;
  }

  #op-api-app .op-token-display input.regular-text {
    font-family: monospace;
    min-width: 320px;
  }

  #op-api-app .op-curl-example {
    display: block;
    background: #1d2327;
    color: #f0f0f1;
    padding: 1rem;
    overflow-x: auto;
    font-family: monospace;
    font-size: 13px;
    line-height: 1.5;
    margin: 0.75rem 0;
    white-space: pre-wrap;
    word-break: break-all;
  }

  #op-api-app .op-param-table {
    border-collapse: collapse;
    margin: 1rem 0;
  }

  #op-api-app .op-param-table th,
  #op-api-app .op-param-table td {
    padding: 0.5rem 0.75rem;
    border: 1px solid #c3c4c7;
    text-align: left;
  }

  #op-api-app .op-param-table thead tr {
    background: #f0f0f1;
  }

  #op-api-app .op-cron-options label {
    display: block;
    margin: 0.5rem 0;
  }

  #op-api-app .op-cron-options label i {
    color: #646970;
  }

  #op-api-app .op-example-actions {
    margin-bottom: 1.5rem;
  }

  [v-cloak] {
    display: none !important;
  }
</style>

<div id="op-api-app" v-cloak>
  <h1>OnPage Cron Import</h1>
  <p>
    Use an API token to trigger imports from cron jobs or external automation without a WordPress login.
    The token is stored in the database and can be generated, regenerated, or disabled from this page.
  </p>

  <div v-if="status.api_token_constant_active" class="op-notice">
    <strong>Remove wp-config constant:</strong>
    The cron API token is now managed here and stored in the database.
    Please remove <code>define('OP_API_TOKEN', …)</code> from <code>wp-config.php</code> —
    it is ignored at runtime and this notice will stay until you remove it.
  </div>

  <div class="op-card">
    <h2 style="margin-top: 0">API token</h2>

    <div v-if="!status.enabled">
      <p>No API token is configured. Cron imports via HTTP are disabled until you generate one.</p>
      <input
        type="button"
        class="button button-primary"
        value="Generate token"
        :disabled="is_busy"
        @click="generateToken()"
      />
    </div>

    <div v-else>
      <p>
        <strong>Status:</strong> enabled
      </p>
      <div class="op-token-display">
        <input class="regular-text code" type="text" readonly :value="status.token" />
        <input
          type="button"
          class="button"
          value="Copy token"
          @click="copyToken()"
        />
      </div>
      <p>
        <input
          type="button"
          class="button"
          value="Regenerate token"
          :disabled="is_busy"
          @click="regenerateToken()"
        />
        <input
          type="button"
          class="button button-danger"
          value="Disable token"
          :disabled="is_busy"
          @click="disableToken()"
        />
      </p>
      <p><i>Regenerating or disabling invalidates the token in any existing cron jobs immediately.</i></p>
    </div>

    <div v-if="is_busy"><i>Working...</i></div>
    <div v-if="message" class="op-notice op-notice-info">{{ message }}</div>
  </div>

  <div class="op-card" v-if="status.enabled">
    <h2 style="margin-top: 0">Cron setup</h2>
    <p>
      Send a <strong>POST</strong> request to your site with the parameters below.
      By default, the import exits immediately when there is no new snapshot — safe to run every minute.
    </p>

    <h3>Import options</h3>
    <div class="op-cron-options">
      <label>
        <input type="checkbox" v-model="cron_options.regen_snapshot" />
        <strong>regen-snapshot</strong> — generate a new snapshot before importing
      </label>
      <label>
        <input type="checkbox" v-model="cron_options.force" />
        <strong>force</strong> — import even if there are no updates from On Page
      </label>
      <label>
        <input type="checkbox" v-model="cron_options.force_slug_regen" />
        <strong>force-slug-regen</strong> — regenerate all slugs
        <br />
        <i>(might slow down the import and is a bad SEO practice — only use in development)</i>
      </label>
    </div>

    <table class="op-param-table">
      <thead>
        <tr>
          <th>Parameter</th>
          <th>Value</th>
          <th>Description</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td><code>op-api</code></td>
          <td><code>import</code></td>
          <td>Run an import</td>
        </tr>
        <tr>
          <td><code>op-token</code></td>
          <td><code>{{ status.token }}</code></td>
          <td>Your secret API token</td>
        </tr>
        <tr v-if="cron_options.regen_snapshot">
          <td><code>regen-snapshot</code></td>
          <td><code>true</code></td>
          <td>Generate a new snapshot before importing</td>
        </tr>
        <tr v-if="cron_options.force">
          <td><code>force</code></td>
          <td><code>true</code></td>
          <td>Import even if there are no updates from On Page</td>
        </tr>
        <tr v-if="cron_options.force_slug_regen">
          <td><code>force-slug-regen</code></td>
          <td><code>true</code></td>
          <td>Regenerate all slugs (development only)</td>
        </tr>
      </tbody>
    </table>

    <h3>Example: curl</h3>
    <code class="op-curl-example">{{ curlExample }}</code>
    <div class="op-example-actions">
      <input type="button" class="button" value="Copy curl command" @click="copyCurl()" />
    </div>

    <h3>Alternative: wp-cli</h3>
    <p>You can also automate imports with WP-CLI (no API token required when run on the server):</p>
    <code class="op-curl-example">{{ wpCliExample }}</code>
    <div class="op-example-actions">
      <input type="button" class="button" value="Copy wp-cli command" @click="copyWpCli()" />
    </div>
  </div>
</div>

<script>
  var OP_CRON_OPTIONS_KEY = 'onpage-cron-import-options'

  function opDefaultCronOptions() {
    return {
      regen_snapshot: false,
      force: false,
      force_slug_regen: false,
    }
  }

  function opLoadCronOptions() {
    try {
      var saved = localStorage.getItem(OP_CRON_OPTIONS_KEY)
      if (!saved) return opDefaultCronOptions()
      return Object.assign(opDefaultCronOptions(), JSON.parse(saved))
    } catch (e) {
      return opDefaultCronOptions()
    }
  }

  new Vue({
    el: '#op-api-app',
    data: {
      status: {
        enabled: false,
        token: null,
        site_url: '',
        api_token_constant_active: false,
      },
      cron_options: opLoadCronOptions(),
      is_busy: false,
      message: '',
    },
    computed: {
      curlExample() {
        if (!this.status.enabled || !this.status.token) return ''
        const base = (this.status.site_url || '').replace(/\/?$/, '/')
        var params = [
          'op-api=import',
          'op-token=' + encodeURIComponent(this.status.token),
        ]
        if (this.cron_options.regen_snapshot) params.push('regen-snapshot=true')
        if (this.cron_options.force) params.push('force=true')
        if (this.cron_options.force_slug_regen) params.push('force-slug-regen=true')
        return "curl -X POST '" + base + '?' + params.join('&') + "'"
      },
      wpCliExample() {
        var parts = ['wp onpage import']
        if (this.cron_options.regen_snapshot) parts.push('--regen-snapshot')
        if (this.cron_options.force) parts.push('--force')
        if (this.cron_options.force_slug_regen) parts.push('--force-slug-regen')
        return parts.join(' ')
      },
    },
    watch: {
      cron_options: {
        deep: true,
        handler(options) {
          try {
            localStorage.setItem(OP_CRON_OPTIONS_KEY, JSON.stringify(options))
          } catch (e) {}
        },
      },
    },
    created() {
      this.loadStatus()
    },
    methods: {
      loadStatus() {
        return axios.post('?op-api=api-token-status').then(res => {
          this.status = res.data
        })
      },
      generateToken() {
        if (this.is_busy) return
        this.is_busy = true
        this.message = ''
        axios.post('?op-api=generate-api-token').then(res => {
          this.status = Object.assign({}, this.status, res.data)
          this.message = 'Token generated. Update your cron jobs with the new token.'
        }).finally(() => {
          this.is_busy = false
        })
      },
      regenerateToken() {
        if (this.is_busy) return
        if (!confirm('Regenerate the API token? Any cron jobs using the current token will stop working until you update them.')) {
          return
        }
        this.is_busy = true
        this.message = ''
        axios.post('?op-api=regenerate-api-token').then(res => {
          this.status = Object.assign({}, this.status, res.data)
          this.message = 'Token regenerated. Update your cron jobs with the new token.'
        }).finally(() => {
          this.is_busy = false
        })
      },
      disableToken() {
        if (this.is_busy) return
        if (!confirm('Disable the API token? Cron imports via HTTP will stop working until you generate a new token.')) {
          return
        }
        this.is_busy = true
        this.message = ''
        axios.post('?op-api=disable-api-token').then(res => {
          this.status = Object.assign({}, this.status, res.data)
          this.message = 'API token disabled.'
        }).finally(() => {
          this.is_busy = false
        })
      },
      copyToken() {
        if (!this.status.token) return
        this.copyText(this.status.token, 'Token copied to clipboard.')
      },
      copyCurl() {
        if (!this.curlExample) return
        this.copyText(this.curlExample, 'curl command copied to clipboard.')
      },
      copyWpCli() {
        if (!this.wpCliExample) return
        this.copyText(this.wpCliExample, 'wp-cli command copied to clipboard.')
      },
      copyText(text, okMessage) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
          navigator.clipboard.writeText(text).then(() => {
            this.message = okMessage
          })
          return
        }
        const el = document.createElement('textarea')
        el.value = text
        document.body.appendChild(el)
        el.select()
        document.execCommand('copy')
        document.body.removeChild(el)
        this.message = okMessage
      },
    },
  })
</script>
