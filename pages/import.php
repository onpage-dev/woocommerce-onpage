<script src="https://cdnjs.cloudflare.com/ajax/libs/vue/2.6.11/vue.min.js" charset="utf-8"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/axios/0.19.2/axios.min.js" charset="utf-8"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/lodash.js/4.17.15/lodash.min.js" charset="utf-8"></script>

<style>
  .op-card {
    background: #fff;
    padding: 1rem;
    border-left: 2px solid #56bd48;
    box-shadow: 0 0 8px -2px rgba(0, 0, 0, .3);
    /* margin: 2rem 0; */
    margin: 1rem;
  }

  .op-card .op-card {
    border-left-width: 2px;
  }

  #op-app h1 {
    color: #56bd48;
  }

  #op-app .button {
    background: #56bd48 !important;
    border: 1px solid #46ad38 !important;
    color: #fff !important;
  }

  #op-app .button.button-danger {
    background: #BF616A !important;
    border: 1px solid #BF616A !important;
    color: #fff !important;
  }

  #op-app h1.danger {
    color: #BF616A !important;
  }

  #op-app .button:disabled {
    opacity: .5;
  }

  #op-app .op-top-header {
    background-color: white;
    box-shadow: 0 -3px 8px -2px rgba(0, 0, 0, .3);
  }

  #op-app .op-top-header .op-navbar {
    /* background-color:red; */
    padding: 0 20px;
    overflow: hidden;
    display: flex;
  }

  #op-app .op-top-header .op-navbar .op-panel-btn {
    /* display:inline-block; */
    padding: 10px;
    cursor: pointer;
  }

  #op-app .op-top-header .op-navbar .op-panel-btn[active] {
    background-color: #eaecf1;
    box-shadow: 0 0 8px -2px rgba(0, 0, 0, .3);
  }

  #op-app .op-panel-box {
    border-top: 2px solid #56bd48;
    background-color: white;

    padding: 1rem 3rem;
    transition-duration: 500ms;
  }

  #op-app .op-content-box {
    display: flex;
    flex-wrap: wrap;
  }

  #op-app .op-card .op-header-card {
    font-size: larger;
  }

  #op-app .op-card-table {
    border-collapse: collapse;
  }

  #op-app .op-card-table,
  #op-app .op-card-table td,
  #op-app .op-card-table th {
    padding: 2px 5px;
    border: 1px solid #46ad3854 !important;
  }

  #op-app .op-card-table thead tr {
    background: #46ad3821;
  }

  #op-app .op-card-table tbody tr:nth-child(even) {
    background: #ededed
  }

  #op-app .op-card-table tbody tr:nth-child(odd) {
    background: #ededed33
  }

  #op-app .op-modal {
    z-index: 1;
    /* Sit on top */
    left: 0;
    top: 0;
    width: 100%;
    /* Full width */
    height: 100%;
    /* Full height */
    position: fixed;
    background-color: rgba(0, 0, 0, 0.4);
    /* Black w/ opacity */
    display: flex;
    justify-content: center;
  }

  #op-app .modal-ext-content {
    margin: auto auto;
    position: relative;
    max-width: 100%;
  }

  #op-app .modal-content {
    /* 15% from the top and centered */
    padding: 3rem 2rem;
    overflow: auto;
    height: auto;
    background-color: #fefefe;
    max-height: 90%;
    /* margin-right: 15%;
  width: 70%; Could be more or less, depending on screen size */
  }

  #op-app .op-card-buttons {
    display: flex;
    padding: 1rem;
  }

  #op-app .op-card-buttons .button {
    margin: 0.5rem;
  }

  #op-app .close {
    color: #666;
    font-size: 30px;
    font-weight: bold;
    position: absolute;
    right: 5px;
    top: 5px;
  }

  #op-app .close:hover,
  #op-app .close:focus {
    color: black;
    text-decoration: none;
    cursor: pointer;
  }

  [v-cloak] {
    display: none !important
  }
</style>

<div id="op-app" style="margin-right: 2rem" v-cloak>
  <div class="op-top-header">
    <div style="text-align: center;">
      <img src="<?= op_link(__DIR__ . '/../logo.png') ?>" alt="" style="max-width: 80%; max-height: 100px;">
      <div style="margin: -1rem 0 2rem"><b>v<?= op_version() ?></b></div>
    </div>

    <div class="op-navbar">
      <div class="op-panel-btn" @click="panel_active='settings'" :active="panel_active=='settings'">
        Setup
      </div>
      <div class="op-panel-btn" @click="panel_active='data-importer'" v-if="next_schema" :active="panel_active=='data-importer'">
        Data Importer
      </div>
      <div class="op-panel-btn" @click="panel_active='import-settings'" v-if="next_schema" :active="panel_active=='import-settings'">
        Import settings
      </div>
      <div class="op-panel-btn" @click="panel_active='variable-names'" v-if="schema" :active="panel_active=='variable-names'">
        Variables
      </div>
      <div class="op-panel-btn" @click="panel_active='update'" :active="panel_active=='update'">
        Update
      </div>
    </div>

  </div>

  <div class="op-panel-box " v-show="panel_active=='settings'">
    <form @submit.prevent="saveSettings">
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
            <th><label>Snapshot token</label></th>
            <td>
              <input class="regular-text code" v-model="settings_form.token" type="password">
            </td>
          </tr>
          <!-- <tr>
            <th><label>Custom routing path</label></th>
            <td>
              <input class="regular-text code" v-model="settings_form.shop_url">
            </td>
          </tr> -->
        </tbody>
      </table>

      <div class="submit">
        <input type="submit" class="button button-primary" value="Save Changes" :disabled="!form_unsaved || is_saving" />
        <div v-if="is_saving">
          Saving...
        </div>
      </div>

    </form>
  </div>

  <div class="op-panel-box " if="next_schema" v-show="panel_active=='data-importer'">
    <h1>Data Importer</h1>
    <label>
      <input type="checkbox" v-model="import_generate_new_snap" />
      Generate a new snapshot before importing
    </label>
    <br>
    <label>
      <input type="checkbox" v-model="import_force_flag" />
      Import even if there are no updates from On Page
    </label>
    <br>
    <label>
      <input type="checkbox" v-model="force_slug_regen" />
      Regenerate all slugs
      <br>
      <i>(might slow down the import and is a bad SEO practice - only use in development).</i>
    </label>
    <br>
    <br>
    <!-- Import button and log -->
    <input type="button" :disabled="is_loading_next_schema || is_importing" class="button button-primary" value="Import data" :disabled="is_importing || is_saving" @click="startImport()">
    <br>
    <i v-if="is_importing">Importing... please wait</i>


    <div v-if="schema && schema.imported_at" style="margin: 1rem 0">
      Last import: {{ schema.imported_at }}
    </div>

    <br>
    <br>
    <i v-if="is_loading_next_schema">Loading...</i>
    <i v-else-if="!next_schema">Configure above</i>

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
        <li>
          {{ res.t_count }} other items
        </li>
      </ul>
      <!-- <pre>{{ res.log.join('\n') }}</pre> -->
    </div>

    <div v-if="snapshots_list && snapshots_list.length">
      Restore old version
      <div v-for="snapshot in snapshots_list" style="padding: 3px;">
        <div class="button" @click="startImport(snapshot)">
          {{snapshot}}
        </div>
      </div>
    </div>

    <div v-if="import_log" style="padding-top: 2rem">
      <div>
        <b style="margin: 2rem">Import log:</b>
      </div>
      <pre v-text="import_log"></pre>
    </div>
  </div>

  <div class="op-panel-box " v-if="next_schema" v-show="panel_active=='import-settings'">
    <h1>Import settings</h1>
    <form @submit.prevent="saveSettings">

      <div style="display: flex; flex-direction: column; gap: 1rem">
        <label>
          <input type="checkbox" v-model="settings_form.maintain_user_prods_and_cats" />
          Maintain user created categories and products
        </label>

        <label>
          <input type="checkbox" v-model="settings_form.disable_product_status_update" />
          Disable product publishing when UPDATING existing products.
          <br />
          (products in the "draft" or "trash" status will not be automatically re-published).
        </label>

        <div v-if="settings_form.disable_product_status_update">
          Default product status for NEW products:
          <br />
          <select placeholder="Default: Active" style="width: 20rem" :value="settings_form[`disable_product_status_update_default_status`] || null" @input="$set(settings_form, `disable_product_status_update_default_status`, $event.target.value || null)">
            <option :value="null">Default: publish</option>
            <option value="publish">Publish</option>
            <option value="draft">Draft</option>
          </select>
        </div>
        <div class="submit">
          <input type="submit" class="button button-primary" value="Save Changes" :disabled="!form_unsaved || is_saving">
          <div v-if="is_saving">
            Saving...
          </div>
        </div>
      </div>

      <hr />

      <div v-for="res in Object.values(next_schema.resources)" v-if="!thing_resources.includes(res.name)">
        <br>
        <h2 style="margin-bottom: 0">{{ res.label }}:</h2>
        <table class="form-table">
          <tbody>
            <tr v-for="property in generic_fields.concat(product_resources.includes(res.name) ? product_fields : [])">
              <td>{{ property.label }}</td>
              <td>
                <div style="display: flex; flex-direction: row; gap: 1rem">
                  <select style="width: 20rem" :value="settings_form[`res-${res.id}-${property.name}`] || null" @input="$set(settings_form, `res-${res.id}-${property.name}`, $event.target.value || null)">
                    <option :value="null">-- not set --</option>
                    <option v-if="property.can_be_empty" value="empty">-- empty --</option>
                    <optgroup label="Fields">
                      <option v-for="field in Object.values(res.fields).filter(x => property.types.includes(x.type))" :value="field.id">{{ field.label }}</option>
                    </optgroup>
                    <optgroup label="Relations">
                      <option v-for="field in Object.values(res.fields).filter(x => x.type == 'relation')" :value="field.id">{{ field.label }}</option>
                    </optgroup>
                  </select>
                  <select v-if="fieldById(settings_form[`res-${res.id}-${property.name}`])?.type == 'relation'" style="width: 20rem" :value="settings_form[`res-${res.id}-${property.name}-2`] || null" @input="$set(settings_form, `res-${res.id}-${property.name}-2`, $event.target.value || null)">
                    <option value="">-- not set --</option>
                    <option v-if="property.can_be_empty" value="empty">-- empty --</option>
                    <option v-for="field in Object.values(relatedFieldResource(settings_form[`res-${res.id}-${property.name}`]).fields).filter(x => property.types.includes(x.type))" :value="field.id">{{ field.label }}</option>
                  </select>
                </div>
                <div v-if="property.note">{{ property.note }}</div>
              </td>
            </tr>
          </tbody>
        </table>
        <div class="submit">
          <input type="submit" class="button button-primary" value="Save Changes" :disabled="!form_unsaved || is_saving">
          <div v-if="is_saving">
            Saving...
          </div>
        </div>
      </div>
    </form>
  </div>

  <div class="op-panel-box " v-if="schema" v-show="panel_active=='variable-names'">
    <h1>Variable names</h1>
    <div class="op-content-box">

      <div v-for="res in _.sortBy(schema.resources, 'label') " class="op-card">

        <div class="op-header-card">
          <div class=""> Resource Label: <b>{{ res.label }}</b></div>
          <div class=""> Resource Name: <b>{{ res.name }}</b></div>
          <div class=""> Resource Model: <b> {{ res.class_name }}</b></div>
        </div>

        <div class="op-card-buttons">
          <div class="button" v-if="Object.values(res.fields).filter(x => x.type == 'relation').length" @click="field_modal=Object.values(res.fields).filter(x => x.type == 'relation')">
            Relations:
            {{Object.values(res.fields).filter(x => x.type == 'relation').length}}
          </div>
          <!-- <table class="op-card-table">
          <thead>
            <tr>
              <th>Name</th>
              <th>Alias</th>
            </tr>
          </thead>
          <tbody>
          <tr v-for="field in Object.values(res.fields).filter(x => x.type == 'relation')">
            <td>{{ field.label }}</td>
            <td>{{ field.name }}</td>        
          </tr>   
          </tbody>
        </table> -->

          <div class="button" v-if="Object.values(res.fields).filter(x => x.type != 'relation').length" @click="field_modal=Object.values(res.fields).filter(x => x.type != 'relation')">
            Fields:
            {{Object.values(res.fields).filter(x => x.type != 'relation').length}}
          </div>

          <!-- <table class="op-card-table">
          <thead>
            <tr>
              <th>Name</th>
              <th>Alias</th>
              <th>Type</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="field in Object.values(res.fields).filter(x => x.type != 'relation')">
              <td>{{ field.label }}</td>
              <td>{{ field.name }}</td>     
              <td>{{ field.type }}</td> 
            </tr>   
          </tbody>
        </table> -->
        </div>
      </div>
    </div>

  </div>

  <div v-show="panel_active=='update'">
    <div class="op-panel-box ">
      <h1>Update plugin</h1>
      <i>Just click this button to download an update from github</i>
      <br>
      <br>
      <input v-if="!is_updating" type="button" class="button button-primary" value="Update plugin" @click="updatePlugin()">
      <i v-else>Upgrading...</i>
      <b class="color: #9e0000">ATTENTION:</b> Mimimum PHP version: 7.4
    </div>
    <div class="op-panel-box ">
      <h1 class="danger">Danger zone</h1>
      <i>This will delete all the product categories and the products! Use at your own risk!</i>
      <br>
      <br>
      <input v-if="!is_deleting" type="button" class="button button-danger" value="Reset products" @click="deleteData()">
      <i v-else>Cleaning up...</i>
    </div>
  </div>

  <div class="op-modal" v-if="field_modal">
    <div class="modal-ext-content">
      <div class="close" @keydown.esc="field_modal=null" @click="field_modal=null">
        &times;
      </div>
      <div class="modal-content">
        <table class="op-card-table">
          <thead>
            <tr>
              <th>Name</th>
              <th>Alias</th>
              <th>Type</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="field in field_modal">
              <td>{{ field.label }}</td>
              <td>{{ field.name }}</td>
              <td>{{ field.type }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div>


<script type="text/javascript">
  axios.defaults.headers = {
    'Cache-Control': 'no-cache',
    'Pragma': 'no-cache',
    'Expires': '0',
  };
  axios.interceptors.response.use(function(response) {
    return response
  }, function(err) {
    if (err.response) {
      if (err.response.status == 400) {
        alert('Error: ' + err.response.data.error)
      } else if (err.response.status == 500) {
        alert(`On Page plugin error ${err.response.status}`)
      } else {
        // nothing
      }
    } else if (err.request) {
      alert('Connection error')
    } else {
      alert('Connection error: ' + err.message)
    }
    return Promise.reject(err)
  })
  new Vue({
    el: '#op-app',
    data: {
      import_log: '',
      panel_active: 'settings',
      import_log_link: <?= json_encode(op_link(op_import_log_path())) ?>,
      settings: <?= json_encode(op_settings()) ?>,
      settings_form: <?= json_encode(op_settings()) ?>,
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
      is_deleting: false,
      is_updating: false,
      is_caching_file: false,
      file_error: true,
      force_slug_regen: false,
      import_generate_new_snap: true,
      import_force_flag: false,
      old_files: [],
      snapshots_list: null,
      field_modal: null,
      server_config: null,
      generic_fields: [{
          name: 'name',
          label: 'Name',
          default: 'auto',
          types: ['string', 'text', 'int', 'real'],
        },
        {
          name: 'slug',
          label: 'Slug',
          default: 'auto',
          types: ['string'],
        },
        {
          name: 'description',
          label: 'Description',
          default: 'auto',
          types: ['string', 'text', 'html', 'int', 'real'],
        },
        {
          name: 'fakeimage',
          label: 'Image',
          default: 'none',
          types: ['image'],
        },
      ],
      product_fields: [

        {
          name: 'sku',
          label: 'Sku',
          default: 'none',
          types: ['string', 'int'],
        },
        {
          name: 'excerpt',
          label: 'Short Description',
          default: 'auto',
          types: ['string', 'text', 'html', 'int', 'real'],
        },
        {
          name: 'weight',
          label: 'Weight',
          default: 'none',
          types: ['weight', 'int', 'real'],
        },
        {
          name: 'length',
          label: 'Length',
          default: 'none',
          types: ['int', 'real'],
        },
        {
          name: 'width',
          label: 'Width',
          default: 'none',
          types: ['int', 'real'],
        },
        {
          name: 'height',
          label: 'Height',
          default: 'none',
          types: ['int', 'real'],
        },
        {
          name: 'price',
          label: 'Price',
          default: 'none',
          types: ['int', 'real', 'price'],
          can_be_empty: true,
        },
        {
          name: 'discounted-price',
          label: 'Discounted Price',
          default: 'none',
          types: ['int', 'real', 'price'],
          can_be_empty: true,
        },
        {
          name: 'discounted-start-date',
          label: 'Discount start date',
          default: 'none',
          types: ['date'],
          can_be_empty: true,
        },
        {
          name: 'discounted-end-date',
          label: 'Discount end date',
          default: 'none',
          types: ['date'],
          can_be_empty: true,
        },
        {
          name: 'downloadable',
          label: 'Downloadable',
          default: 'none',
          types: ['bool'],
          can_be_empty: true,
        },
        {
          name: 'manage_stock',
          label: 'Manage stock',
          default: 'off',
          types: ['bool'],
        },
        {
          name: 'stock',
          label: 'Stock (available pieces)',
          default: 'infinity',
          types: ['int'],
        },
        {
          name: 'low_stock_amount',
          label: 'Low stock threshold',
          default: 'none',
          types: ['int'],
        },
        {
          name: 'stock_status',
          label: 'In stock (true/false)',
          default: 'if stock > 0',
          types: ['bool'],
        },
        {
          name: 'virtual',
          label: 'Virtual product',
          default: 'none',
          types: ['bool'],
        },
        {
          name: 'image',
          label: 'Image',
          default: 'none',
          note: 'WARNING: importing images in the Wordpress Gallery will greatly slow down the import process and is generally not needed',
          types: ['image'],
        },
      ],
    },
    computed: {
      product_resources() {
        return this.server_config?.product_resources ?? []
      },
      thing_resources() {
        return this.server_config?.thing_resources ?? []
      },
      form_unsaved() {
        console.log(JSON.stringify(this.settings))
        console.log(JSON.stringify(this.settings_form))
        return JSON.stringify(this.settings) != JSON.stringify(this.settings_form)
      },
      connection_string() {
        return (this.settings.company || '') + (this.settings.token || '')
      },
      non_imported_files() {
        return (this.files || []).filter(x => !x.is_imported)
      },
      // ordered_res: function (){
      //   return this.schema.resources.sort(function(a, b){

      //     return a.name[0] -b.name[0]}) 
      // }
    },
    created() {
      this.refreshSchema()
      this.getSnapshotsList()
      this.getServerConfig()
      setInterval(() => {
        axios.get(this.import_log_link).then(res => {
          this.import_log = res.data
        })
      }, 2000)
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
      startImport(file_name) {
        this.is_importing = true
        this.import_result = null
        axios.post(location.pathname, {}, {
            params: {
              'op-api': 'import',
              force_slug_regen: this.force_slug_regen,
              regen_snapshot: this.import_generate_new_snap,
              force: this.import_force_flag,
              file_name
            }
          }).then(res => {
            alert('Import completed!')
            this.import_result = res.data
            this.refreshSchema()
            this.getSnapshotsList()
          })
          .finally(res => {
            this.is_importing = false
          })
      },
      refreshNextSchema() {
        this.is_loading_next_schema = true
        axios.post('?op-api=next-schema').then(res => {
            this.next_schema = res.data
            this.panel_active = 'data-importer'
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
        if (!files.length || this.is_caching_file) return null
        clearTimeout(this._file_timeout)
        this.is_caching_file = true

        console.log('caching', files)

        axios.post('?op-api=import-files', {
            files
          }).then(res => {
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
        if (this.is_updating) return null
        this.is_updating = true

        axios.post(`?op-api=upgrade`).then(res => {
            alert('Upgrade completed')
            location.reload()
          }, err => console.log(err.message))
          .finally(res => {
            this.is_updating = false
          })
      },
      deleteData() {
        if (this.is_deleting) return null

        if (!confirm("This will erase all the data you manage with woocommerce, do you want to proceed?")) {
          return null
        }

        this.is_deleting = true

        axios.post(`?op-api=reset-data`).then(res => {
            alert('Data has been deleted')
          }, err => console.log(err.message))
          .finally(res => {
            this.is_deleting = false
          })
      },

      toCamel(str) {
        return str.split('_').map(x => {
          return (x[0] || '').toLocaleUpperCase() + x.substring(1)
        }).join('')
      },
      getSnapshotsList() {
        axios.post(`?op-api=snapshots-list`).then(res => {
          this.snapshots_list = res.data
        })
      },
      getServerConfig() {
        axios.post(`?op-api=server-config`).then(res => {
          this.server_config = res.data
        })
      },
      fieldById(id) {
        for (const r of this.next_schema.resources)
          for (const f of r.fields)
            if (f.id == id) return f
      },
      relatedFieldResource(id) {
        const f = this.fieldById(id)
        if (!f) return
        for (const r of this.next_schema.resources)
          if (r.id == f.rel_res_id) return r
      },
    },

    watch: {
      connection_string: {
        immediate: true,
        handler(s) {
          if (s) {
            this.refreshNextSchema()
          }
        },
      },
      schema() {
        this.refreshFiles()
        this.refreshOldFiles()
      },
    },
  })
</script>