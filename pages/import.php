<script src="https://cdnjs.cloudflare.com/ajax/libs/vue/2.6.11/vue.min.js" charset="utf-8"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/axios/0.19.2/axios.min.js" charset="utf-8"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/lodash.js/4.17.15/lodash.min.js" charset="utf-8"></script>

<style file="screen">
.op-card {
  background: #fff;
  padding: 1rem;
  border-left: 4px solid #56bd48;
  box-shadow: 0 0 8px -2px rgba(0,0,0,.3);
  margin: 2rem 0;
}
.op-card .op-card {
  border-left-width: 2px;
}
#op-app h1 {
  color: #56bd48;
}
#op-app .button {
  background: #56bd48!important;
  border: 1px solid #46ad38!important;
  color: #fff!important;
}
#op-app .button:disabled {
  opacity: .5;
}
</style>

<div id="op-app" style="margin-right: 2rem">
  <form @submit.prevent="saveSettings" class="op-card">
    <img src="<?=op_link(__DIR__.'/../logo.png')?>" alt="" style="max-width: 80%; max-height: 160px;">
    <h1>OnPage&reg; Woocommerce Plugin 1.0.8</h1>
    <table class="form-table">
    	<tbody>
        <tr>
          <th><label>Company name (e.g. dinside)</label></th>
          <td>
            <input class="regular-text code" v-model="settings_form.company">
            <br>
            <i style="margin-top: 4px" v-if="settings_form.company">Your domain is <a :href="`https://${settings_form.company}.onpage.it`" target="_blank">{{ `${settings_form.company}.onpage.it` }}</a></i>
          </td>
        </tr>
        <tr>
          <th><label>API token</label></th>
          <td>
            <input class="regular-text code" v-model="settings_form.token">
          </td>
        </tr>
      </tbody>
    </table>

    <p class="submit">
      <input type="submit" class="button button-primary" value="Save Changes" :disabled="!form_unsaved || is_saving">
      <div v-if="is_saving">
        Saving...
      </div>
    </p>

  </form>


  <div v-if="next_schema" class="op-card">
    <h1>Data Importer</h1>
    <label>
      <input type="checkbox" v-model="force_slug_regen"/>
      Force slug field regeneration for existing objects
      <br>
      <i>(might slow down the import and is a bad SEO practice - only use in development).</i>
    </label>
    <br>
    <br>
    <!-- Import button and log -->
    <input type="button" :disabled="is_loading_next_schema || is_importing" class="button button-primary" value="Import data" :disabled="is_importing || is_saving" @click="startImport">
    <div v-if="schema.imported_at" style="margin: 1rem 0">
      Last import: {{ schema.imported_at }}
    </div>
    <br>
    <br>
    <i v-if="is_loading_next_schema">Loading...</i>
    <i v-else-if="!next_schema">Configure above</i>
    <i v-else-if="is_importing">Importing... please wait</i>
    <div v-if="res = import_result">
      <b style="margin: 0 0 .5rem">Import result:</b>
      <br>
      Import took {{ (res.time).toFixed(2) }} seconds
      <br>
      <ul>
        <li>
          {{ res.c_count }} categories
        </li>
        <li>
          {{ res.p_count }} products
        </li>
      </ul>
      <pre>{{ res.log.join('\n') }}</pre>
    </div>
  </div>

  <div class="op-card">
    <h1>Import settings</h1>

    <form @submit.prevent="saveSettings" v-if="next_schema">
      <div v-for="res in Object.values(next_schema.resources).filter(x => x.is_product)" >
        <br>
        <h2>{{ res.label }}:</h2>
        <table class="form-table">
          <tbody>
            <tr>
              <th><label>Price field</label></th>
              <td>
                <select v-model="settings_form[`res-${res.id}-price`]">
                  <option :value="undefined">-- not set --</option>
                  <option v-for="field in Object.values(res.fields).filter(x => ['real', 'int'].includes(x.type))"
                    :value="field.id">{{ field.label }}</option>
                </select>
              </td>
            </tr>
            <tr>
              <th><label>SKU field</label></th>
              <td>
                <select v-model="settings_form[`res-${res.id}-sku`]">
                  <option :value="undefined">-- not set --</option>
                  <option v-for="field in Object.values(res.fields).filter(x => ['string', 'int'].includes(x.type))"
                    :value="field.id">{{ field.label }}</option>
                </select>
              </td>
            </tr>
            <tr>
              <th><label>Weight</label></th>
              <td>
                <select v-model="settings_form[`res-${res.id}-weight`]">
                  <option :value="undefined">-- not set --</option>
                  <option v-for="field in Object.values(res.fields).filter(x => ['real', 'int'].includes(x.type))"
                    :value="field.id">{{ field.label }}</option>
                </select>
              </td>
            </tr>
            <tr>
              <th><label>Length</label></th>
              <td>
                <select v-model="settings_form[`res-${res.id}-length`]">
                  <option :value="undefined">-- not set --</option>
                  <option v-for="field in Object.values(res.fields).filter(x => ['real', 'int'].includes(x.type))"
                    :value="field.id">{{ field.label }}</option>
                </select>
              </td>
            </tr>
            <tr>
              <th><label>Width</label></th>
              <td>
                <select v-model="settings_form[`res-${res.id}-width`]">
                  <option :value="undefined">-- not set --</option>
                  <option v-for="field in Object.values(res.fields).filter(x => ['real', 'int'].includes(x.type))"
                    :value="field.id">{{ field.label }}</option>
                </select>
              </td>
            </tr>
            <tr>
              <th><label>Height</label></th>
              <td>
                <select v-model="settings_form[`res-${res.id}-height`]">
                  <option :value="undefined">-- not set --</option>
                  <option v-for="field in Object.values(res.fields).filter(x => ['real', 'int'].includes(x.type))"
                    :value="field.id">{{ field.label }}</option>
                </select>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <p class="submit">
        <input type="submit" class="button button-primary" value="Save Changes" :disabled="!form_unsaved || is_saving">
        <div v-if="is_saving">
          Saving...
        </div>
      </p>
    </form>
  </div>

  <div v-if="schema" class="op-card">
    <h1>File importer</h1>

    <div v-if="is_loading_file || !files">
      Loading...
    </div>
    <div v-else>
      <b>You have imported {{ files.length - non_imported_files.length }} / {{ files.length }} files.</b>
      <div v-if="file_error">
        Error while importing file, please <a @click.prevent="cacheFiles()" href="#">click here</a> to try again
      </div>
      <div v-else-if="non_imported_files.length > 0">
        We are importing the rest, please do not close this page.
      </div>
      <div v-else>
        All your files have been imported :-)
      </div>
    </div>

    <div v-if="old_files.length">
      <hr>
      <h2>There are {{ old_files.length }} old files</h2>


      <input type="button" class="button button-primary" value="Drop old files" :disabled="is_loading_old_files || is_dropping_old_files" @click="dropOldFiles">
    </div>
  </div>

  <div v-if="schema" class="op-card" style="line-height: 1.8">
    <h1>Variable names</h1>

    <div v-for="res in schema.resources" class="op-card">
      <h2 style="margin: 0 0 0"><b>{{ res.label }}</b>: <code>{{ res.name }} | {{ toCamel(res.name) }}</code></h2>
      <div style="margin: 0.3rem 0"><b>Relations:</b></div>
      <div v-for="field in Object.values(res.fields).filter(x => x.type == 'relation')">
        {{ field.label }}: <code>{{ field.name }}</code>
      </div>
      <div style="margin: 0.3rem 0"><b>Fields:</b></div>
      <div v-for="field in Object.values(res.fields).filter(x => x.type != 'relation')">
        {{ field.label }}: <code>{{ field.name }} | {{ field.type }}</code>
      </div>
    </div>
  </div>

  <div class="op-card">
    <h1>Update plugin</h1>
    <i>Just click this button to download an update from github</i>
    <br>
    <br>
    <input v-if="!is_updating" type="button" class="button button-primary" value="Update plugin" @click="updatePlugin()">
    <i v-else>Upgrading...</i>
  </div>
</div>


<script type="text/javascript">
axios.interceptors.response.use(function (response) {
  return response
}, function (err) {
  if (err.response) {
    if (err.response.status == 400) {
      alert('Error: '+err.response.data.error)
    } else {
      alert(`Error ${err.response.status}`)
    }
  } else if (err.request) {
    alert('Request error')
  } else {
    alert('Connection error: ' + err.message)
  }
  return Promise.reject(err)
})

new Vue({
  el: '#op-app',
  data: {
    settings: <?=json_encode(op_settings())?>,
    settings_form: <?=json_encode(op_settings())?>,
    is_saving: false,
    is_importing: false,
    is_loading_schema: false,
    is_loading_next_schema: false,
    is_dropping_old_files: false,
    import_result: null,
    schema: null,
    next_schema: null,
    files: null,
    is_loading_file: false,
    is_loading_old_files: false,
    is_updating: false,
    is_caching_file: false,
    file_error: true,
    force_slug_regen: false,
    old_files: [],
  },
  computed: {
    form_unsaved () {
      return JSON.stringify(this.settings) != JSON.stringify(this.settings_form)
    },
    connection_string() {
      return (this.settings.company||'')+(this.settings.token||'')
    },
    non_imported_files () {
      return (this.files || []).filter(x => !x.is_imported)
    },
  },
  created () {
    this.refreshSchema()
  },
  methods: {
    saveSettings() {
      this.is_saving = true
      axios.post('?op-api=save-settings', {
        settings: this.settings_form,
      }).then(res => {
        console.log(res.data)
        this.settings = _.clone(res.data)
      })
      .finally(res => {
        this.is_saving = false
      })
    },
    startImport() {
      this.is_importing = true
      this.import_result = null
      axios.post('?op-api=import', {
        settings: this.settings_form,
        force_slug_regen: this.force_slug_regen,
      }).then(res => {
        alert('Import completed!')
        this.import_result = res.data
        this.refreshSchema()
      })
      .finally(res => {
        this.is_importing = false
      })
    },
    refreshNextSchema() {
      this.is_loading_next_schema = true
      axios.post('?op-api=next-schema').then(res => {
        this.next_schema = res.data
      })
      .finally(res => {
        this.is_loading_next_schema = false
      })
    },
    refreshSchema() {
      this.is_loading_schema = true
      axios.post('?op-api=schema').then(res => {
        this.schema = res.data
      })
      .finally(res => {
        this.is_loading_schema = false
      })
    },
    refreshFiles() {
      this.is_loading_file = true
      axios.post('?op-api=list-files').then(res => {
        this.files = res.data
        this.cacheFiles()
      })
      .finally(res => {
        this.is_loading_file = false
      })
    },
    refreshOldFiles() {
      this.is_loading_old_files = true
      axios.post('?op-api=list-old-files').then(res => {
        this.old_files = res.data
      })
      .finally(res => {
        this.is_loading_old_files = false
      })
    },
    dropOldFiles() {
      this.is_dropping_old_files = true
      axios.post('?op-api=drop-old-files').then(res => {
        this.old_files = res.data
      })
      .finally(res => {
        this.is_dropping_old_files = false
      })
    },
    cacheFiles() {
      this.file_error = false
      let files = this.non_imported_files.slice(0, 4)
      if (!files.length || this.is_caching_file) return
      clearTimeout(this._file_timeout)
      this.is_caching_file = true

      console.log('caching', files)

      axios.post('?op-api=import-files', { files }).then(res => {
        for (var token in res.data) {
          let m = files.find(x => x.info.token == token)
          let ok = res.data[token]
          if (ok) {
            this.$set(m, 'is_imported', true)
            this.$delete(m, 'error')
          } else this.$set(m, 'error', true)
        }
        this._file_timeout = setTimeout(() => this.cacheFiles(), 300)
      }, err => {
        this.file_error = err
      })
      .finally(res => {
        this.is_caching_file = false
      })
    },
    updatePlugin() {
      if (this.is_updating) return
      this.is_updating = true

      axios.post(`?op-api=upgrade`).then(res => {
        alert('Upgrade completato')
        location.reload()
      }, err => console.log(err.message))
      .finally(res => {
        this.is_updating = false
      })
    },

    toCamel (str) {
      return str.split('_').map(x => {
        return (x[0] || '').toLocaleUpperCase() + x.substring(1)
      }).join('')
    },
  },

  watch: {
    connection_string: {
      immediate: true,
      handler (s) {
        if (s) {
          this.refreshNextSchema()
        }
      },
    },
    schema () {
      this.refreshFiles()
      this.refreshOldFiles()
    },
  },
})
</script>
