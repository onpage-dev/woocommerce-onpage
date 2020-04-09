<script src="https://cdnjs.cloudflare.com/ajax/libs/vue/2.6.11/vue.min.js" charset="utf-8"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/axios/0.19.2/axios.min.js" charset="utf-8"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/lodash.js/4.17.15/lodash.min.js" charset="utf-8"></script>

<style media="screen">
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
    <img src="<?=op_path_url(__DIR__.'/../logo.png')?>" alt="" style="max-width: 80%; max-height: 100px;">
    <h1>OnPage&reg; Woocommerce Plugin 1.0</h1>
    <table class="form-table">
    	<tbody>
        <tr>
          <th><label>Company name (e.g. lithos)</label></th>
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
        <tr>
          <th><label>Shop url</label></th>
          <td>
            <input class="regular-text code" v-model="settings_form.shop_url">
          </td>
        </tr>
      </tbody>
    </table>


    <div v-if="is_loading_schema">
      Loading...
    </div>
    <div v-else-if="schema">
      <h1>Import settings</h1>
      <div v-for="res in Object.values(schema.resources).filter(x => x.is_product)" >
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
    </div>

    <p class="submit">
      <input type="submit" class="button button-primary" value="Save Changes" :disabled="!form_unsaved || is_saving">
      <div v-if="is_saving">
        Saving...
      </div>
    </p>

  </form>


  <div v-if="schema" class="op-card">
    <h1>Data importer</h1>


    <!-- Import button and log -->
    <input type="button" class="button button-primary" value="Import data" :disabled="is_importing || is_saving" @click="startImport">
    <br>
    <br>
    <i v-if="is_importing">Importing... please wait</i>
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


  <div v-if="schema" class="op-card">
    <h1>File importer</h1>

    <div v-if="is_loading_media || !media">
      Loading...
    </div>
    <div v-else>
      <b>You have imported {{ media.length - non_imported_media.length }} / {{ media.length }} files.</b>
      <div v-if="non_imported_media.length > 0">
        We are importing the rest, please do not close this page.
      </div>
      <div v-else>
        All your files have been imported :-)
      </div>
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
    import_result: null,
    schema: null,
    media: null,
    is_loading_media: false,
    is_updating: false,
    is_caching_media: false,
  },
  computed: {
    form_unsaved () {
      return JSON.stringify(this.settings) != JSON.stringify(this.settings_form)
    },
    connection_string() {
      return (this.settings.company||'')+(this.settings.token||'')
    },
    non_imported_media () {
      return (this.media || []).filter(x => !x.is_imported)
    },
  },
  created () {
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
      axios.post('?op-api=import').then(res => {
        alert('Import completed!')
        this.import_result = res.data
        this.refreshMedia()
      })
      .finally(res => {
        this.is_importing = false
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
    refreshMedia() {
      this.is_loading_media = true
      axios.post('?op-api=media').then(res => {
        this.media = res.data
        this.cacheMedia()
      })
      .finally(res => {
        this.is_loading_media = false
      })
    },
    cacheMedia() {
      let file = this.non_imported_media[0]
      if (!file || this.is_caching_media) return
      clearTimeout(this._media_timeout)

      this.is_caching_media = true

      console.log('caching', file.token)


      axios.post(`?op-api=cache-media&token=${file.token}`).then(res => {
        this.$set(file, 'is_imported', true)
      }, err => console.log(err.message))
      .finally(res => {
        this.is_caching_media = false
        this._media_timeout = setTimeout(() => this.cacheMedia(), 300)
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
        return x[0].toLocaleUpperCase() + x.substring(1)
      }).join('')
    },
  },

  watch: {
    connection_string: {
      immediate: true,
      handler (s) {
        if (s) {
          this.refreshSchema()
        }
      },
    },
    schema () {
      this.refreshMedia()
    }
  },
})
</script>
