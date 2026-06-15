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

  #op-app .op-notice {
    padding: 0.75rem 1rem;
    margin: 1rem 0;
    border-left: 4px solid #dba617;
    background: #fcf9e8;
  }

  #op-app .op-notice-info {
    border-left-color: #72aee6;
    background: #f0f6fc;
  }

  #op-app .op-header-notice {
    margin: 0 2rem 1rem;
    text-align: left;
  }

  #op-app .op-resource-actions {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
  }

  #op-app .op-wizard-steps {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 1.5rem;
  }

  #op-app .op-wizard-step {
    padding: 0.25rem 0.75rem;
    border-radius: 999px;
    background: #eaecf1;
    color: #666;
  }

  #op-app .op-wizard-step[active] {
    background: #56bd48;
    color: #fff;
  }

  #op-app .op-wizard-type-option {
    display: block;
    padding: 1rem;
    margin-bottom: 0.75rem;
    border: 2px solid #eaecf1;
    cursor: pointer;
  }

  #op-app .op-wizard-type-option[selected] {
    border-color: #56bd48;
    background: #56bd4814;
  }

  #op-app .op-modal .modal-content {
    min-width: 32rem;
    max-width: 40rem;
  }

  #op-app .op-wizard-footer {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    margin-top: 1.5rem;
  }

  #op-app .op-static-term-search {
    position: relative;
    max-width: 28rem;
  }

  #op-app .op-static-term-results {
    position: absolute;
    z-index: 10;
    left: 0;
    right: 0;
    margin: 0;
    padding: 0;
    list-style: none;
    background: #fff;
    border: 1px solid #ccd0d4;
    box-shadow: 0 2px 8px rgba(0, 0, 0, .12);
    max-height: 16rem;
    overflow-y: auto;
  }

  #op-app .op-static-term-results li {
    padding: 0.5rem 0.75rem;
    cursor: pointer;
    border-bottom: 1px solid #eaecf1;
  }

  #op-app .op-static-term-results li:hover {
    background: #56bd4814;
  }

  #op-app .op-lang-add-row {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    align-items: center;
    margin: 0.75rem 0 1.5rem;
  }

  #op-app .op-lang-add-row.op-fallback-add {
    align-items: flex-start;
    flex-direction: column;
  }

  #op-app .op-fallback-chain {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.35rem;
    max-width: 36rem;
  }

  #op-app .op-fallback-chain-primary {
    font-weight: 600;
  }

  #op-app .op-fallback-chain-arrow {
    color: #646970;
    user-select: none;
  }

  #op-app .op-fallback-chain-part {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
  }

  #op-app .op-fallback-chain-step {
    display: inline-flex;
    align-items: center;
    gap: 0.15rem;
    padding: 0.15rem 0.35rem;
    background: #f0f0f1;
    border: 1px solid #c3c4c7;
    border-radius: 3px;
  }

  #op-app .op-fallback-chain-step button {
    border: none;
    background: transparent;
    cursor: pointer;
    color: #646970;
    padding: 0 0.2rem;
    line-height: 1;
    font-size: 0.85rem;
  }

  #op-app .op-fallback-chain-step button:hover:not(:disabled) {
    color: #2271b1;
  }

  #op-app .op-fallback-chain-step button:disabled {
    opacity: 0.35;
    cursor: default;
  }

  #op-app .op-fallback-chain-add {
    min-width: 9rem;
  }

  #op-app .op-lang-field-error {
    color: #b32d2e;
    margin: 0.25rem 0 0;
  }
</style>

<div id="op-app" style="margin-right: 2rem" v-cloak>
  <div class="op-top-header">
    <div style="text-align: center;">
      <img src="<?= op_link(__DIR__ . '/../logo.png') ?>" alt="" style="max-width: 80%; max-height: 100px;">
      <div style="margin: -1rem 0 1rem"><b>v<?= op_version() ?></b></div>
    </div>

    <div v-if="resourceTypesCodeHooksActive" class="op-notice op-header-notice">
      <strong>Remove theme code:</strong>
      Resource import types are now managed in <strong>Import settings</strong> and stored in the database.
      Please remove <code>add_filter('op_resource_types', …)</code> and any legacy
      <code>on_page_product_resources</code> hook from your theme <code>functions.php</code> —
      the code hook is ignored and this notice will stay until you remove it.
    </div>

    <div v-if="importRelationsCodeHooksActive" class="op-notice op-header-notice">
      <strong>Remove theme code:</strong>
      WordPress parent linking is now managed in <strong>Import settings</strong> and stored in the database.
      Please remove <code>add_action('op_import_relations', …)</code> from your theme <code>functions.php</code> —
      the code hook is ignored and this notice will stay until you remove it.
    </div>

    <div v-if="staticTermsCodeHooksActive" class="op-notice op-header-notice">
      <strong>Remove theme code:</strong>
      Protected categories are now managed in <strong>Import settings</strong> and stored in the database.
      Please remove <code>add_filter('op_static_terms', …)</code> from your theme <code>functions.php</code> —
      the code hook is ignored and this notice will stay until you remove it.
    </div>

    <div v-if="languageLegacyCodeActive" class="op-notice op-header-notice">
      <strong>Remove theme code:</strong>
      Language mapping is now managed in <strong>Import settings</strong> and stored in the database.
      Please remove <code>set_op_locale_to_lang(…)</code> and <code>op_set_fallback_lang(…)</code>
      from your theme <code>functions.php</code> —
      the code calls are ignored and this notice will stay until you remove them.
    </div>

    <div v-if="fileSettingsConstantsActive" class="op-notice op-header-notice">
      <strong>Remove wp-config constants:</strong>
      File import settings are now managed in <strong>Import settings</strong> and stored in the database.
      Please remove <code>OP_DISABLE_ORIGINAL_FILE_IMPORT</code> and <code>OP_THUMBNAIL_FORMAT</code>
      <code>define()</code> entries from <code>wp-config.php</code> — they are ignored and this notice will stay until you remove them.
    </div>

    <div v-if="apiTokenConstantActive" class="op-notice op-header-notice">
      <strong>Remove wp-config constant:</strong>
      The cron API token is now managed in <strong>WooCommerce → OnPage Cron Import</strong> and stored in the database.
      Please remove <code>define('OP_API_TOKEN', …)</code> from <code>wp-config.php</code> —
      it is ignored and this notice will stay until you remove it.
    </div>

    <div v-if="schemaResourceTypesMismatch" class="op-notice op-notice-info op-header-notice">
      Stored import types differ from your current settings. Save and run a new import to apply changes.
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
            <th><label>Snapshot token</label></th>
            <td>
              <input class="regular-text code" v-model="settings_form.token" type="password">
            </td>
          </tr>
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
          <input type="checkbox" v-model="settings_form.link_all_parent_categories" />
          Link all parent categories for products only (when a product has multiple categories in the relation, assign all of them).
          <br />
          <i>If unchecked, only the first parent category is linked (default). Category hierarchy still uses a single parent.</i>
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

        <hr />

        <h2>Files</h2>
        <label>
          <input type="checkbox" v-model="settings_form.disable_original_file_import" />
          Serve original files from On Page CDN (do not store originals on this server)
          <br />
          <i>Files are still downloaded on first use by default. Thumbnails are always generated and cached locally when requested.</i>
        </label>

        <div>
          Thumbnail format:
          <br />
          <select style="width: 20rem" v-model="settings_form.thumbnail_format">
            <option value="png">PNG (default)</option>
            <option value="jpg">JPG</option>
            <option value="webp">WebP</option>
          </select>
        </div>

        <label>
          <input type="checkbox" v-model="settings_form.enable_imported_at_meta" />
          Store <code>op_imported_at</code> meta on imported products
          <br />
          <i>Also updates <code>post_modified</code> when existing products change during import.</i>
        </label>

        <div class="submit">
          <input type="submit" class="button button-primary" value="Save Changes" :disabled="!form_unsaved || is_saving">
          <div v-if="is_saving">
            Saving...
          </div>
        </div>
      </div>

      <hr />

      <h2>WooCommerce resources</h2>
      <p>
        Configure which OnPage resources are imported into WooCommerce and how they link to WordPress parents.
        All other resources
        <strong>({{ hiddenResourceCount }} of {{ next_schema.resources.length }})</strong>
        use the hidden high-performance table automatically.
        Parent linking uses one OnPage relation field per resource (category hierarchy or product→category assignment);
        see <strong>Link all parent categories</strong> above when a product has multiple category relations.
      </p>

      <table v-if="configuredResourceRows.length" class="form-table">
        <thead>
          <tr>
            <th>Resource</th>
            <th>Import as</th>
            <th>WordPress parent</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="res in configuredResourceRows">
            <td>
              <strong>{{ res.label }}</strong>
              <br /><code>{{ res.name }}</code>
            </td>
            <td>{{ resourceTypeLabel(res.name) }}</td>
            <td>{{ parentLinkLabel(res.name) }}</td>
            <td>
              <div class="op-resource-actions">
                <input type="button" class="button" value="Edit" @click="openResourceModal(res)">
                <input type="button" class="button" value="Use hidden table" @click="removeConfiguredResource(res.name)">
              </div>
            </td>
          </tr>
        </tbody>
      </table>
      <p v-else><i>No WooCommerce resources configured yet. Configure only resources that need product pages, category pages, or WordPress hierarchy.</i></p>

      <p>
        <input type="button" class="button button-primary" value="Configure resource" @click="openResourceModal()" :disabled="!resourceModalAvailableResources.length">
      </p>

      <div class="submit">
        <input type="submit" class="button button-primary" value="Save Changes" :disabled="!form_unsaved || is_saving">
        <div v-if="is_saving">Saving...</div>
      </div>

      <hr />

      <h2>Protected categories</h2>
      <p>
        WooCommerce categories listed here are never updated or deleted by the import.
        Fixed parent categories configured under WooCommerce resources are protected automatically too.
        Use primary-language category IDs; all WPML translations are protected automatically.
      </p>

      <table v-if="staticTermRows.length" class="form-table">
        <thead>
          <tr>
            <th>Category</th>
            <th>ID</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="row in staticTermRows">
            <td>
              <strong>{{ row.name }}</strong>
              <br /><code>{{ row.slug }}</code>
            </td>
            <td>{{ row.id }}</td>
            <td>
              <input type="button" class="button" value="Remove" @click="removeStaticTerm(row.id)">
            </td>
          </tr>
        </tbody>
      </table>
      <p v-else><i>No protected categories configured.</i></p>

      <div class="op-static-term-search">
        <input
          class="regular-text"
          type="search"
          placeholder="Search categories to protect…"
          v-model="static_term_search"
          @input="queueStaticTermSearch"
        >
        <ul v-if="static_term_results.length" class="op-static-term-results">
          <li v-for="cat in static_term_results" @click="addStaticTerm(cat)">
            <strong>{{ cat.name }}</strong>
            <br /><code>{{ cat.slug }}</code> · ID {{ cat.id }}
          </li>
        </ul>
        <p v-if="static_term_searching"><i>Searching…</i></p>
      </div>

      <div class="submit">
        <input type="submit" class="button button-primary" value="Save Changes" :disabled="!form_unsaved || is_saving">
        <div v-if="is_saving">Saving...</div>
      </div>

      <hr />

      <h2>Language mapping</h2>
      <p>
        Configure OnPage fallback order when translations are missing.
        With WPML, you can also map each WPML language to an OnPage language code.
      </p>

      <p v-if="!availableOnpageLangs.length"><i>Save your snapshot token first to load available OnPage languages.</i></p>

      <template v-else>
        <template v-if="wpmlEnabled">
          <h3>WPML language → OnPage language</h3>
          <p>
            Pick from active WPML languages when locale codes differ from OnPage (e.g. <code>en</code> → <code>en_gb</code>).
            Unmapped locales still resolve automatically (e.g. <code>de_de</code> → <code>de</code>).
          </p>

          <p v-if="!wpmlLanguages.length" class="op-notice op-notice-info">
            WPML is active but no languages were returned. Check WPML setup, then reload this page.
          </p>

          <template v-else>
            <table v-if="localeToLangRows.length" class="form-table">
              <thead>
                <tr>
                  <th>WPML language</th>
                  <th>OnPage language</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="row in localeToLangRows">
                  <td>
                    <strong>{{ row.wpml.name }}</strong>
                    <span v-if="row.wpml.is_default"> (default)</span>
                    <br /><code>{{ row.locale }}</code>
                    · WPML <code>{{ row.wpml.code }}</code>
                  </td>
                  <td><code>{{ row.lang }}</code></td>
                  <td>
                    <input type="button" class="button" value="Remove" @click="removeLocaleMapping(row.locale)">
                  </td>
                </tr>
              </tbody>
            </table>
            <p v-else><i>No locale mappings configured.</i></p>

            <div class="op-lang-add-row">
              <select v-model="new_locale_mapping.locale" :disabled="!unmappedWpmlLanguages.length">
                <option value="">— WPML language —</option>
                <option v-for="w in unmappedWpmlLanguages" :value="w.locale">
                  {{ w.name }} ({{ w.code }}) · {{ w.locale }}
                </option>
              </select>
              <select v-model="new_locale_mapping.lang" :disabled="!new_locale_mapping.locale">
                <option value="">— OnPage language —</option>
                <option v-for="lang in availableOnpageLangs" :value="lang">{{ lang }}</option>
              </select>
              <input
                type="button"
                class="button"
                value="Add mapping"
                @click="addLocaleMapping"
                :disabled="!canAddLocaleMapping"
              >
            </div>
            <p v-if="locale_mapping_error" class="op-lang-field-error">{{ locale_mapping_error }}</p>
            <p v-if="!unmappedWpmlLanguages.length"><i>All active WPML languages are mapped.</i></p>
          </template>
        </template>
        <p v-else class="op-notice op-notice-info" style="margin: 1rem 0;">
          <strong>WPML not active.</strong>
          Locale mapping is only available with WPML. WordPress locales are matched to OnPage automatically.
        </p>

        <template v-if="multilingualOnpageProject">
          <h3>Fallback languages</h3>
          <p>
            When a translation is missing, try these OnPage languages <strong>in order</strong> after the current one.
            If no chain is set, the importer uses the country-free variant and then the primary OnPage project language.
          </p>

          <table v-if="fallbackLangRows.length" class="form-table">
            <thead>
              <tr>
                <th>OnPage language</th>
                <th>Then try (in order)</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in fallbackLangRows">
                <td>
                  <code>{{ row.lang }}</code>
                  <span v-if="wpmlLabelForOnpageLang(row.lang)"><br /><i>{{ wpmlLabelForOnpageLang(row.lang) }}</i></span>
                </td>
                <td>
                  <div class="op-fallback-chain">
                    <span class="op-fallback-chain-primary"><code>{{ row.lang }}</code></span>
                    <span
                      v-for="(step, idx) in settings_form.fallback_langs[row.lang]"
                      :key="row.lang + '-' + step"
                      class="op-fallback-chain-part"
                    >
                      <span class="op-fallback-chain-arrow">→</span>
                      <span class="op-fallback-chain-step">
                        <code>{{ step }}</code>
                        <button type="button" title="Move up" :disabled="idx === 0" @click.stop="moveFallbackStep(row.lang, idx, -1)">↑</button>
                        <button type="button" title="Move down" :disabled="idx === settings_form.fallback_langs[row.lang].length - 1" @click.stop="moveFallbackStep(row.lang, idx, 1)">↓</button>
                        <button type="button" title="Remove" @click.stop="removeFallbackStep(row.lang, idx)">×</button>
                      </span>
                    </span>
                    <select
                      class="op-fallback-chain-add"
                      :key="'fb-add-' + row.lang + '-' + (settings_form.fallback_langs[row.lang] || []).length"
                      @change="addFallbackStep(row.lang, $event)"
                      :disabled="!fallbackChainOptions(row.lang, settings_form.fallback_langs[row.lang] || []).length"
                    >
                      <option value="">+ Add step…</option>
                      <option v-for="lang in fallbackChainOptions(row.lang, settings_form.fallback_langs[row.lang] || [])" :value="lang">{{ lang }}</option>
                    </select>
                  </div>
                </td>
                <td>
                  <input type="button" class="button" value="Remove" @click="removeFallbackChain(row.lang)">
                </td>
              </tr>
            </tbody>
          </table>
          <p v-else><i>No custom fallback chains configured.</i></p>

          <div class="op-lang-add-row op-fallback-add">
            <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center;">
              <select v-model="new_fallback.lang">
                <option value="">— OnPage language —</option>
                <option
                  v-for="lang in availableFallbackPrimaryLangs"
                  :value="lang"
                >{{ lang }}</option>
              </select>
              <input
                type="button"
                class="button"
                value="Add fallback chain"
                @click="addFallbackChain"
                :disabled="!canAddFallbackChain"
              >
            </div>
            <div v-if="new_fallback.lang" class="op-fallback-chain">
              <span class="op-fallback-chain-primary"><code>{{ new_fallback.lang }}</code></span>
              <span
                v-for="(step, idx) in new_fallback.steps"
                :key="new_fallback.lang + '-new-' + step"
                class="op-fallback-chain-part"
              >
                <span class="op-fallback-chain-arrow">→</span>
                <span class="op-fallback-chain-step">
                  <code>{{ step }}</code>
                  <button type="button" title="Move up" :disabled="idx === 0" @click.stop="moveNewFallbackStep(idx, -1)">↑</button>
                  <button type="button" title="Move down" :disabled="idx === new_fallback.steps.length - 1" @click.stop="moveNewFallbackStep(idx, 1)">↓</button>
                  <button type="button" title="Remove" @click.stop="removeNewFallbackStep(idx)">×</button>
                </span>
              </span>
              <select
                class="op-fallback-chain-add"
                :key="'new-fb-' + new_fallback.lang + '-' + new_fallback.steps.length"
                @change="addNewFallbackStep($event)"
                :disabled="!fallbackChainOptions(new_fallback.lang, new_fallback.steps).length"
              >
                <option value="">+ Add step…</option>
                <option v-for="lang in fallbackChainOptions(new_fallback.lang, new_fallback.steps)" :value="lang">{{ lang }}</option>
              </select>
            </div>
            <p v-if="new_fallback.lang && !new_fallback.steps.length"><i>Add at least one fallback language.</i></p>
          </div>
        </template>
        <p v-else><i>Fallback chains are available when the OnPage project has multiple languages.</i></p>
      </template>

      <div class="submit">
        <input type="submit" class="button button-primary" value="Save Changes" :disabled="!form_unsaved || is_saving">
        <div v-if="is_saving">Saving...</div>
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
                    <option :value="null">{{ property.none_label ??  '-- not set --' }}</option>
                    <option v-if="property.can_be_empty" value="empty">-- empty --</option>
                    <option v-for="opt in property.custom_fields" :value="opt.value">{{ opt.label }}</option>
                    <optgroup label="Fields">
                      <option v-for="field in Object.values(res.fields).filter(x => property.types.includes(x.type))" :value="field.id">{{ field.label }}</option>
                    </optgroup>
                    <optgroup label="Relations">
                      <option v-for="field in Object.values(res.fields).filter(x => x.type === 'relation')" :value="field.id">{{ field.label }}</option>
                    </optgroup>
                  </select>
                  <select v-if="fieldById(settings_form[`res-${res.id}-${property.name}`])?.type === 'relation'" style="width: 20rem" :value="settings_form[`res-${res.id}-${property.name}-2`] || null" @input="$set(settings_form, `res-${res.id}-${property.name}-2`, $event.target.value || null)">
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
          <div class="button" v-if="Object.values(res.fields).filter(x => x.type === 'relation').length" @click="field_modal=Object.values(res.fields).filter(x => x.type === 'relation')">
            Relations:
            {{Object.values(res.fields).filter(x => x.type === 'relation').length}}
          </div>
          <!-- <table class="op-card-table">
          <thead>
            <tr>
              <th>Name</th>
              <th>Alias</th>
            </tr>
          </thead>
          <tbody>
          <tr v-for="field in Object.values(res.fields).filter(x => x.type === 'relation')">
            <td>{{ field.label }}</td>
            <td>{{ field.name }}</td>
          </tr>
          </tbody>
        </table> -->

          <div class="button" v-if="Object.values(res.fields).filter(x => x.type !== 'relation').length" @click="field_modal=Object.values(res.fields).filter(x => x.type !== 'relation')">
            Fields:
            {{Object.values(res.fields).filter(x => x.type !== 'relation').length}}
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
            <tr v-for="field in Object.values(res.fields).filter(x => x.type !== 'relation')">
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
      <br>
      <br>
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

  <div class="op-modal" v-if="resource_modal">
    <div class="modal-ext-content">
      <div class="close" @click="closeResourceModal">&times;</div>
      <div class="modal-content">
        <h2 style="margin-top: 0">{{ resource_modal.editing ? 'Edit resource' : 'Configure resource' }}</h2>

        <div class="op-wizard-steps">
          <span class="op-wizard-step" :active="resource_modal.step === 1 || undefined">1. Resource</span>
          <span class="op-wizard-step" :active="resource_modal.step === 2 || undefined">2. Import type</span>
          <span class="op-wizard-step" :active="resource_modal.step === 3 || undefined">3. WordPress parent</span>
        </div>

        <div v-if="resource_modal.step === 1">
          <p>Choose which OnPage resource should be imported into WooCommerce.</p>
          <select style="width: 100%" v-model="resource_modal.resource_name">
            <option :value="null" disabled>Select a resource...</option>
            <option v-for="res in resourceModalStepResources" :value="res.name">{{ res.label }} ({{ res.name }})</option>
          </select>
        </div>

        <div v-if="resource_modal.step === 2">
          <p>
            How should <strong>{{ resourceModalSelectedResource ? resourceModalSelectedResource.label : resource_modal.resource_name }}</strong>
            be imported?
          </p>
          <label class="op-wizard-type-option" :selected="resource_modal.type === 'post' || undefined" @click="resource_modal.type = 'post'">
            <strong>Product</strong>
            <br />
            <span>Creates WooCommerce products — use for sellable items with their own product pages.</span>
          </label>
          <label class="op-wizard-type-option" :selected="resource_modal.type === 'term' || undefined" @click="resource_modal.type = 'term'">
            <strong>Category</strong>
            <br />
            <span>Creates WooCommerce product categories — use for taxonomy pages and category hierarchy.</span>
          </label>
        </div>

        <div v-if="resource_modal.step === 3">
          <p>
            Optional: how should imported
            <strong>{{ resourceModalSelectedResource ? resourceModalSelectedResource.label : resource_modal.resource_name }}</strong>
            items link to WordPress parents?
          </p>

          <label style="display: block; margin-bottom: 0.5rem;">
            <input type="radio" v-model="resource_modal.parent_mode" value="relation">
            OnPage relation field
          </label>
          <label style="display: block; margin-bottom: 1rem;">
            <input type="radio" v-model="resource_modal.parent_mode" value="fixed">
            Fixed WordPress category (auto-protected)
          </label>

          <div v-if="resource_modal.parent_mode === 'relation'">
            <select style="width: 100%" v-model="resource_modal.relation_name">
              <option :value="null">— None (no automatic parent linking) —</option>
              <option v-for="field in resourceModalRelationFields" :value="field.name">{{ relationFieldLabel(field) }}</option>
            </select>
            <p v-if="!resourceModalAllRelationFields.length"><i>This resource has no relation fields in the snapshot.</i></p>
            <p v-else-if="!resourceModalRelationFields.length"><i>No valid parent relations: configure the target resource as a WooCommerce category first, then pick a relation that points to it.</i></p>
            <p v-else-if="resource_modal.type === 'term'"><i>Category hierarchy: parent must be another configured WooCommerce category.</i></p>
            <p v-else><i>Product categories: relation must point to a configured WooCommerce category.</i></p>
          </div>

          <div v-else>
            <p><i>Every imported item is assigned to one WordPress category. That category is never updated or deleted by the import.</i></p>
            <p v-if="resource_modal.fixed_category_id">
              <strong>{{ resourceModalFixedCategoryLabel }}</strong>
              <br /><code>ID {{ resource_modal.fixed_category_id }}</code>
              <br />
              <input type="button" class="button" value="Clear" @click="clearFixedParentCategory">
            </p>
            <div class="op-static-term-search">
              <input
                class="regular-text"
                type="search"
                placeholder="Search categories…"
                v-model="resource_modal.fixed_category_search"
                @input="queueFixedParentSearch"
              >
              <ul v-if="resource_modal.fixed_category_results.length" class="op-static-term-results">
                <li v-for="cat in resource_modal.fixed_category_results" @click="selectFixedParentCategory(cat)">
                  <strong>{{ cat.name }}</strong>
                  <br /><code>{{ cat.slug }}</code> · ID {{ cat.id }}
                </li>
              </ul>
              <p v-if="resource_modal.fixed_category_searching"><i>Searching…</i></p>
            </div>
          </div>
        </div>

        <div class="op-wizard-footer">
          <div>
            <input v-if="resource_modal.step > 1" type="button" class="button" value="Back" @click="resource_modal.step--">
          </div>
          <div style="display: flex; gap: 0.5rem;">
            <input type="button" class="button" value="Cancel" @click="closeResourceModal">
            <input v-if="resource_modal.step === 1" type="button" class="button button-primary" value="Next" :disabled="!resource_modal.resource_name" @click="resource_modal.step = 2">
            <input v-if="resource_modal.step === 2" type="button" class="button button-primary" value="Next" @click="goResourceModalStep3">
            <input v-if="resource_modal.step === 3" type="button" class="button button-primary" value="Save" @click="saveResourceModal">
          </div>
        </div>
      </div>
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
      if (err.response.status === 400) {
        alert('Error: ' + err.response.data.error)
      } else if (err.response.status === 500) {
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
      resource_modal: null,
      server_config: null,
      static_term_search: '',
      static_term_results: [],
      static_term_labels: {},
      static_term_searching: false,
      _static_term_search_timeout: null,
      new_locale_mapping: { locale: '', lang: '' },
      new_fallback: { lang: '', steps: [] },
      locale_mapping_error: '',
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
        {
          name: 'sorting',
          label: 'Sorting',
          default: 'none',
          none_label: 'Same as On Page (default)',
          note: 'By default, sorting will reflect the On Page ordering, but you can use any numeric field to set the order or maintain the wordpress custom sorting',
          types: ['int'],
          custom_fields: [
            {
              label: 'Maintain wordpress sorting',
              value: '_wp_sorting',
            }
          ]
        },
      ],
    },
    computed: {
      product_resources() {
        if (!this.next_schema) return this.server_config?.product_resources ?? []
        return this.next_schema.resources
          .filter(r => this.resourceType(r.name) === 'post')
          .map(r => r.name)
      },
      thing_resources() {
        if (!this.next_schema) return this.server_config?.thing_resources ?? []
        return this.next_schema.resources
          .filter(r => this.resourceType(r.name) === 'thing')
          .map(r => r.name)
      },
      resourceTypesCodeHooksActive() {
        return !!(this.server_config && this.server_config.resource_types_code_hooks_active)
      },
      importRelationsCodeHooksActive() {
        return !!(this.server_config && this.server_config.import_relations_code_hooks_active)
      },
      staticTermsCodeHooksActive() {
        return !!(this.server_config && this.server_config.static_terms_code_hooks_active)
      },
      languageLegacyCodeActive() {
        return !!(this.server_config && this.server_config.language_legacy_code_active)
      },
      fileSettingsConstantsActive() {
        return !!(this.server_config && this.server_config.file_settings_constants_active)
      },
      apiTokenConstantActive() {
        return !!(this.server_config && this.server_config.api_token_constant_active)
      },
      schemaResourceTypesMismatch() {
        return !!(this.server_config && this.server_config.schema_resource_types_mismatch)
      },
      configuredResourceRows() {
        if (!this.next_schema) return []
        const types = this.settings_form.resource_types || {}
        const resources = this.next_schema.resources.filter(r => types[r.name] === 'post' || types[r.name] === 'term')
        return this.sortResourcesByHierarchy(resources)
      },
      hiddenResourceCount() {
        if (!this.next_schema) return 0
        return this.next_schema.resources.length - this.configuredResourceRows.length
      },
      resourceModalAvailableResources() {
        if (!this.next_schema) return []
        const types = this.settings_form.resource_types || {}
        return _.sortBy(
          this.next_schema.resources.filter(r => types[r.name] !== 'post' && types[r.name] !== 'term'),
          'label'
        )
      },
      resourceModalStepResources() {
        if (!this.resource_modal) return []
        if (this.resource_modal.editing) {
          const res = this.schemaResourceByName(this.resource_modal.resource_name)
          return res ? [res] : []
        }
        return this.resourceModalAvailableResources
      },
      resourceModalSelectedResource() {
        return this.schemaResourceByName(this.resource_modal && this.resource_modal.resource_name)
      },
      resourceModalRelationFields() {
        const res = this.resourceModalSelectedResource
        if (!res || !this.resource_modal) return []
        return this.validParentRelationFields(res, this.resource_modal.type)
      },
      resourceModalAllRelationFields() {
        const res = this.resourceModalSelectedResource
        if (!res) return []
        return this.relationFieldsForResource(res)
      },
      resourceModalFixedCategoryLabel() {
        if (!this.resource_modal || !this.resource_modal.fixed_category_id) return ''
        const label = this.resource_modal.fixed_category_label
        return label ? label.name : `Category #${this.resource_modal.fixed_category_id}`
      },
      staticTermRows() {
        const ids = this.settings_form.static_terms || []
        return ids.map(id => {
          const label = this.static_term_labels[id]
          return {
            id,
            name: label ? label.name : `Category #${id}`,
            slug: label ? label.slug : '',
          }
        })
      },
      availableOnpageLangs() {
        if (this.next_schema && this.next_schema.langs) return this.next_schema.langs
        if (this.schema && this.schema.langs) return this.schema.langs
        return this.server_config?.onpage_langs || []
      },
      wpmlEnabled() {
        return !!(this.server_config && this.server_config.wpml && this.server_config.wpml.enabled)
      },
      wpmlLanguages() {
        if (!this.wpmlEnabled) return []
        return this.server_config.wpml.languages || []
      },
      multilingualOnpageProject() {
        return this.availableOnpageLangs.length > 1
      },
      languagePickerLangs() {
        return this.multilingualOnpageProject ? this.availableOnpageLangs : []
      },
      wpmlOnpageLangEntries() {
        const map = this.settings_form.locale_to_lang || {}
        const entries = []
        const seen = new Set()
        for (const w of this.wpmlLanguages) {
          const onpage = map[w.locale]
          if (!onpage || !this.availableOnpageLangs.includes(onpage)) continue
          if (seen.has(onpage)) continue
          seen.add(onpage)
          entries.push({ onpage, wpml: w })
        }
        return entries
      },
      unmappedWpmlLanguages() {
        const mapped = new Set(Object.keys(this.settings_form.locale_to_lang || {}))
        return this.wpmlLanguages.filter(w => !mapped.has(w.locale))
      },
      localeToLangRows() {
        if (!this.wpmlEnabled) return []
        const map = this.settings_form.locale_to_lang || {}
        const rows = []
        for (const w of this.wpmlLanguages) {
          if (!map[w.locale]) continue
          rows.push({ locale: w.locale, lang: map[w.locale], wpml: w })
        }
        return rows
      },
      fallbackLangRows() {
        const map = this.settings_form.fallback_langs || {}
        const rows = []
        const seen = new Set()
        for (const onpage of this.languagePickerLangs) {
          if (!map[onpage]) continue
          rows.push({
            lang: onpage,
            chain: this.sanitizeFallbackChain(onpage, map[onpage] || []),
          })
          seen.add(onpage)
        }
        for (const lang of Object.keys(map)) {
          if (seen.has(lang)) continue
          rows.push({
            lang,
            chain: this.sanitizeFallbackChain(lang, map[lang] || []),
          })
        }
        return rows
      },
      availableFallbackPrimaryLangs() {
        return this.languagePickerLangs.filter(lang => !this.hasFallbackChain(lang))
      },
      canAddLocaleMapping() {
        if (!this.wpmlEnabled || !this.new_locale_mapping.locale || !this.new_locale_mapping.lang) return false
        if (!this.isValidOnpageLang(this.new_locale_mapping.lang)) return false
        const locale = this.normalizeLocaleKey(this.new_locale_mapping.locale)
        if (!locale || !this.wpmlLanguageByLocale(locale)) return false
        if (this.settings_form.locale_to_lang && this.settings_form.locale_to_lang[locale]) return false
        return true
      },
      canAddFallbackChain() {
        if (!this.multilingualOnpageProject || !this.new_fallback.lang) return false
        if (!this.languagePickerLangs.includes(this.new_fallback.lang)) return false
        if (this.hasFallbackChain(this.new_fallback.lang)) return false
        return this.new_fallback.steps.length > 0
      },
      form_unsaved() {
        return JSON.stringify(this.settings) !== JSON.stringify(this.settings_form)
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
        this.sanitizeLanguageSettings()
        this.is_saving = true
        axios.post('?op-api=save-settings', {
            settings: this.settings_form,
          }).then(res => {
            this.settings = _.cloneDeep(res.data)
            this.settings_form = _.cloneDeep(res.data)
            this.sanitizeLanguageSettings()
            this.getServerConfig()
          })
          .finally(res => {
            this.is_saving = false
          })
      },
      resourceType(name) {
        const types = this.settings_form.resource_types || {}
        const t = types[name]
        if (t === 'post' || t === 'term') return t
        return 'thing'
      },
      resourceTypeLabel(name) {
        const t = this.resourceType(name)
        if (t === 'post') return 'Product'
        if (t === 'term') return 'Category'
        return 'Hidden (high performance)'
      },
      schemaResourceByName(name) {
        if (!this.next_schema || !name) return null
        return this.next_schema.resources.find(r => r.name === name) || null
      },
      setResourceType(name, type) {
        if (!this.settings_form.resource_types) {
          this.$set(this.settings_form, 'resource_types', {})
        }
        if (type === 'thing') {
          this.$delete(this.settings_form.resource_types, name)
          return
        }
        this.$set(this.settings_form.resource_types, name, type)
      },
      removeConfiguredResource(name) {
        this.setResourceType(name, 'thing')
        this.removeImportRelation(name)
      },
      ensureImportRelations() {
        if (!this.settings_form.import_relations) {
          this.$set(this.settings_form, 'import_relations', {})
        }
      },
      setImportRelation(resourceName, relationName) {
        this.ensureImportRelations()
        this.$set(this.settings_form.import_relations, resourceName, relationName)
      },
      removeImportRelation(resourceName) {
        if (!this.settings_form.import_relations) return
        this.$delete(this.settings_form.import_relations, resourceName)
      },
      relationFieldsForResource(res) {
        if (!res || !res.fields) return []
        return Object.values(res.fields).filter(f => f.type === 'relation')
      },
      validParentRelationFields(res, sourceType) {
        if (!res || (sourceType !== 'post' && sourceType !== 'term')) return []
        return this.relationFieldsForResource(res).filter(field => {
          const target = this.schemaResourceById(field.rel_res_id)
          if (!target) return false
          return this.resourceType(target.name) === 'term'
        })
      },
      isFixedParent(value) {
        return typeof value === 'number' || (/^\d+$/).test(String(value))
      },
      sanitizeResourceModalRelation() {
        if (!this.resource_modal || this.resource_modal.parent_mode === 'fixed') return
        if (!this.resource_modal.relation_name) return
        const res = this.resourceModalSelectedResource
        if (!res) {
          this.resource_modal.relation_name = null
          return
        }
        const valid = this.validParentRelationFields(res, this.resource_modal.type)
        if (!valid.some(f => f.name === this.resource_modal.relation_name)) {
          this.resource_modal.relation_name = null
        }
      },
      sanitizeAllImportRelations() {
        if (!this.next_schema || !this.settings_form.import_relations) return
        const relations = { ...this.settings_form.import_relations }
        let changed = false
        for (const resourceName of Object.keys(relations)) {
          const res = this.schemaResourceByName(resourceName)
          const type = this.resourceType(resourceName)
          const parent = relations[resourceName]
          if (!res || (type !== 'post' && type !== 'term')) {
            delete relations[resourceName]
            changed = true
            continue
          }
          if (this.isFixedParent(parent)) {
            continue
          }
          if (typeof parent !== 'string') {
            delete relations[resourceName]
            changed = true
            continue
          }
          if (!this.validParentRelationFields(res, type).some(f => f.name === parent)) {
            delete relations[resourceName]
            changed = true
          }
        }
        if (changed) {
          this.$set(this.settings_form, 'import_relations', relations)
        }
      },
      goResourceModalStep3() {
        this.resource_modal.step = 3
        this.sanitizeResourceModalRelation()
      },
      relationFieldLabel(field) {
        const target = this.schemaResourceById(field.rel_res_id)
        const targetLabel = target ? target.label : '?'
        return field.label + ' → ' + targetLabel + ' (' + field.name + ')'
      },
      parentRelationLabel(resourceName, relationName) {
        if (typeof relationName === 'number' || (/^\d+$/).test(String(relationName))) {
          return 'Fixed WordPress category #' + relationName
        }
        const res = this.schemaResourceByName(resourceName)
        if (!res) return relationName
        const field = Object.values(res.fields || {}).find(f => f.name === relationName)
        return field ? this.relationFieldLabel(field) : relationName
      },
      parentLinkLabel(resourceName) {
        const relations = this.settings_form.import_relations || {}
        const rel = relations[resourceName]
        if (!rel) return '—'
        return this.parentRelationLabel(resourceName, rel)
      },
      schemaResourceById(id) {
        if (!this.next_schema || !id) return null
        return this.next_schema.resources.find(r => r.id === id) || null
      },
      getConfiguredParentResourceName(resourceName, configuredNames) {
        const relations = this.settings_form.import_relations || {}
        const relationName = relations[resourceName]
        if (!relationName || typeof relationName !== 'string') return null
        const res = this.schemaResourceByName(resourceName)
        if (!res) return null
        const field = Object.values(res.fields || {}).find(f => f.name === relationName)
        if (!field) return null
        const target = this.schemaResourceById(field.rel_res_id)
        if (!target || !configuredNames.has(target.name)) return null
        return target.name
      },
      sortResourcesByHierarchy(resources) {
        const configuredNames = new Set(resources.map(r => r.name))
        const childrenOf = {}
        for (const res of resources) {
          const parent = this.getConfiguredParentResourceName(res.name, configuredNames) || ''
          if (!childrenOf[parent]) childrenOf[parent] = []
          childrenOf[parent].push(res)
        }
        const sortSiblings = (list) => _.sortBy(list, [
          r => (this.resourceType(r.name) === 'term' ? 0 : 1),
          'label',
        ])
        const result = []
        const visited = new Set()
        const visit = (parentKey) => {
          for (const child of sortSiblings(childrenOf[parentKey] || [])) {
            if (visited.has(child.name)) continue
            visited.add(child.name)
            result.push(child)
            visit(child.name)
          }
        }
        visit('')
        const orphans = sortSiblings(resources.filter(r => !visited.has(r.name)))
        return result.concat(orphans)
      },
      syncImportRelationsFromSettings() {
        this.ensureImportRelations()
      },
      openResourceModal(res) {
        const relations = this.settings_form.import_relations || {}
        if (res) {
          const rel = relations[res.name]
          const isFixed = this.isFixedParent(rel)
          this.resource_modal = {
            step: 2,
            resource_name: res.name,
            type: this.resourceType(res.name),
            parent_mode: isFixed ? 'fixed' : 'relation',
            relation_name: isFixed ? null : (rel || null),
            fixed_category_id: isFixed ? parseInt(rel, 10) : null,
            fixed_category_label: null,
            fixed_category_search: '',
            fixed_category_results: [],
            fixed_category_searching: false,
            _fixed_category_search_timeout: null,
            editing: true,
          }
          if (isFixed) this.loadFixedParentLabel(this.resource_modal.fixed_category_id)
          return
        }
        this.resource_modal = {
          step: 1,
          resource_name: null,
          type: 'post',
          parent_mode: 'relation',
          relation_name: null,
          fixed_category_id: null,
          fixed_category_label: null,
          fixed_category_search: '',
          fixed_category_results: [],
          fixed_category_searching: false,
          _fixed_category_search_timeout: null,
          editing: false,
        }
      },
      closeResourceModal() {
        this.resource_modal = null
      },
      saveResourceModal() {
        if (!this.resource_modal || !this.resource_modal.resource_name) return
        this.sanitizeResourceModalRelation()
        const name = this.resource_modal.resource_name
        this.setResourceType(name, this.resource_modal.type)
        if (this.resource_modal.parent_mode === 'fixed' && this.resource_modal.fixed_category_id) {
          this.setImportRelation(name, this.resource_modal.fixed_category_id)
        } else if (this.resource_modal.relation_name) {
          this.setImportRelation(name, this.resource_modal.relation_name)
        } else {
          this.removeImportRelation(name)
        }
        this.closeResourceModal()
      },
      loadFixedParentLabel(id) {
        if (!id || !this.resource_modal) return
        axios.post('?op-api=search-categories', {}, { params: { ids: [id] } })
          .then(res => {
            const label = res.data && res.data[id]
            if (label && this.resource_modal) {
              this.resource_modal.fixed_category_label = label
            }
          })
      },
      queueFixedParentSearch() {
        if (!this.resource_modal) return
        clearTimeout(this.resource_modal._fixed_category_search_timeout)
        if (!this.resource_modal.fixed_category_search.trim()) {
          this.resource_modal.fixed_category_results = []
          this.resource_modal.fixed_category_searching = false
          return
        }
        this.resource_modal._fixed_category_search_timeout = setTimeout(() => this.searchFixedParentCategories(), 250)
      },
      searchFixedParentCategories() {
        if (!this.resource_modal) return
        const q = this.resource_modal.fixed_category_search.trim()
        if (!q) {
          this.resource_modal.fixed_category_results = []
          return
        }
        this.resource_modal.fixed_category_searching = true
        axios.post('?op-api=search-categories', {}, { params: { q } })
          .then(res => {
            if (!this.resource_modal) return
            this.resource_modal.fixed_category_results = res.data || []
          })
          .finally(() => {
            if (this.resource_modal) this.resource_modal.fixed_category_searching = false
          })
      },
      selectFixedParentCategory(cat) {
        if (!this.resource_modal || !cat || !cat.id) return
        this.resource_modal.fixed_category_id = cat.id
        this.resource_modal.fixed_category_label = cat
        this.resource_modal.fixed_category_search = ''
        this.resource_modal.fixed_category_results = []
      },
      clearFixedParentCategory() {
        if (!this.resource_modal) return
        this.resource_modal.fixed_category_id = null
        this.resource_modal.fixed_category_label = null
        this.resource_modal.fixed_category_search = ''
        this.resource_modal.fixed_category_results = []
      },
      syncResourceTypesFromSchema() {
        if (!this.settings_form.resource_types) {
          this.$set(this.settings_form, 'resource_types', {})
        }
        if (!this.settings_form.static_terms) {
          this.$set(this.settings_form, 'static_terms', [])
        }
        if (this.settings_form.disable_original_file_import === undefined) {
          this.$set(this.settings_form, 'disable_original_file_import', false)
        }
        if (!this.settings_form.thumbnail_format) {
          this.$set(this.settings_form, 'thumbnail_format', 'png')
        }
        if (this.settings_form.enable_imported_at_meta === undefined) {
          this.$set(this.settings_form, 'enable_imported_at_meta', false)
        }
        this.ensureLanguageSettings()
        this.sanitizeLanguageSettings()
        this.syncImportRelationsFromSettings()
        this.sanitizeAllImportRelations()
      },
      ensureLanguageSettings() {
        if (!this.settings_form.locale_to_lang) {
          this.$set(this.settings_form, 'locale_to_lang', {})
        }
        if (!this.settings_form.fallback_langs) {
          this.$set(this.settings_form, 'fallback_langs', {})
        }
      },
      isValidOnpageLang(lang) {
        return !!lang && this.availableOnpageLangs.includes(lang)
      },
      isValidLanguagePickerLang(lang) {
        return !!lang && this.languagePickerLangs.includes(lang)
      },
      wpmlLanguageByLocale(locale) {
        const key = this.normalizeLocaleKey(locale)
        return this.wpmlLanguages.find(w => w.locale === key) || null
      },
      guessOnpageLangForWpml(w) {
        if (!w) return null
        const schema = this.availableOnpageLangs
        if (schema.includes(w.code)) return w.code
        if (schema.includes(w.locale)) return w.locale
        const prefix = (w.locale || '').split('_')[0]
        if (prefix && schema.includes(prefix)) return prefix
        return null
      },
      wpmlLabelForOnpageLang(onpage) {
        const entry = this.wpmlOnpageLangEntries.find(e => e.onpage === onpage)
        return entry ? `WPML: ${entry.wpml.name}` : ''
      },
      hasFallbackChain(lang) {
        return !!(this.settings_form.fallback_langs && this.settings_form.fallback_langs[lang])
      },
      fallbackChainOptions(primaryLang, chain) {
        const used = new Set([primaryLang, ...(chain || [])])
        return this.languagePickerLangs.filter(lang => !used.has(lang))
      },
      sanitizeFallbackChain(primaryLang, chain) {
        const seen = new Set([primaryLang])
        const out = []
        for (const lang of chain || []) {
          if (!this.isValidLanguagePickerLang(lang)) continue
          if (seen.has(lang)) continue
          seen.add(lang)
          out.push(lang)
        }
        return out
      },
      sanitizeLanguageSettings() {
        this.ensureLanguageSettings()

        if (this.wpmlEnabled) {
          const wpmlLocales = new Set(this.wpmlLanguages.map(w => w.locale))
          const locales = {}
          for (const [rawLocale, lang] of Object.entries(this.settings_form.locale_to_lang || {})) {
            const locale = this.normalizeLocaleKey(rawLocale)
            if (!locale || !wpmlLocales.has(locale)) continue
            if (!this.isValidOnpageLang(lang)) continue
            locales[locale] = lang
          }
          this.$set(this.settings_form, 'locale_to_lang', locales)
        }

        if (!this.multilingualOnpageProject) {
          this.$set(this.settings_form, 'fallback_langs', {})
          return
        }

        const allowedLangs = new Set(this.languagePickerLangs)
        const fallbacks = {}
        for (const lang of this.availableOnpageLangs) {
          if (!allowedLangs.has(lang) || !this.settings_form.fallback_langs[lang]) continue
          const sanitized = this.sanitizeFallbackChain(lang, this.settings_form.fallback_langs[lang])
          if (sanitized.length) fallbacks[lang] = sanitized
        }
        for (const lang of Object.keys(this.settings_form.fallback_langs || {})) {
          if (fallbacks[lang] || !allowedLangs.has(lang)) continue
          const sanitized = this.sanitizeFallbackChain(lang, this.settings_form.fallback_langs[lang])
          if (sanitized.length) fallbacks[lang] = sanitized
        }
        this.$set(this.settings_form, 'fallback_langs', fallbacks)
      },
      normalizeLocaleKey(locale) {
        return (locale || '').trim().toLowerCase().replace(/-/g, '_')
      },
      addLocaleMapping() {
        this.locale_mapping_error = ''
        if (!this.wpmlEnabled) return
        this.ensureLanguageSettings()
        const locale = this.normalizeLocaleKey(this.new_locale_mapping.locale)
        const lang = this.new_locale_mapping.lang
        if (!locale || !lang) return
        if (!this.wpmlLanguageByLocale(locale)) {
          this.locale_mapping_error = 'Choose an active WPML language.'
          return
        }
        if (!this.isValidOnpageLang(lang)) {
          this.locale_mapping_error = 'Choose a valid OnPage language.'
          return
        }
        if (this.settings_form.locale_to_lang[locale]) {
          this.locale_mapping_error = `“${locale}” is already mapped. Remove the existing row first.`
          return
        }
        this.$set(this.settings_form.locale_to_lang, locale, lang)
        this.new_locale_mapping = { locale: '', lang: '' }
      },
      removeLocaleMapping(locale) {
        if (!this.settings_form.locale_to_lang) return
        this.$delete(this.settings_form.locale_to_lang, locale)
        this.locale_mapping_error = ''
      },
      resetNewFallbackDraft() {
        this.new_fallback = { lang: '', steps: [] }
      },
      addFallbackChain() {
        if (!this.multilingualOnpageProject) return
        this.ensureLanguageSettings()
        const lang = this.new_fallback.lang
        const chain = this.sanitizeFallbackChain(lang, this.new_fallback.steps)
        if (!lang || !chain.length || !this.isValidLanguagePickerLang(lang)) return
        if (this.hasFallbackChain(lang)) return
        this.$set(this.settings_form.fallback_langs, lang, chain)
        this.resetNewFallbackDraft()
      },
      setFallbackChain(lang, chain) {
        this.ensureLanguageSettings()
        const sanitized = this.sanitizeFallbackChain(lang, chain)
        if (!sanitized.length) {
          this.$delete(this.settings_form.fallback_langs, lang)
          return
        }
        this.$set(this.settings_form.fallback_langs, lang, sanitized)
      },
      addFallbackStep(lang, event) {
        const step = event.target.value
        event.target.value = ''
        if (!step || !this.isValidLanguagePickerLang(step)) return
        const chain = [...(this.settings_form.fallback_langs[lang] || [])]
        if (lang === step || chain.includes(step)) return
        chain.push(step)
        this.setFallbackChain(lang, chain)
      },
      removeFallbackStep(lang, index) {
        const chain = [...(this.settings_form.fallback_langs[lang] || [])]
        chain.splice(index, 1)
        this.setFallbackChain(lang, chain)
      },
      moveFallbackStep(lang, index, direction) {
        const chain = (this.settings_form.fallback_langs[lang] || []).slice()
        const target = index + direction
        if (target < 0 || target >= chain.length) return
        const tmp = chain[index]
        chain[index] = chain[target]
        chain[target] = tmp
        this.$set(this.settings_form.fallback_langs, lang, chain)
      },
      addNewFallbackStep(event) {
        const step = event.target.value
        event.target.value = ''
        if (!step || !this.isValidLanguagePickerLang(step)) return
        if (step === this.new_fallback.lang || this.new_fallback.steps.includes(step)) return
        this.new_fallback.steps.push(step)
      },
      removeNewFallbackStep(index) {
        this.new_fallback.steps.splice(index, 1)
      },
      moveNewFallbackStep(index, direction) {
        const steps = this.new_fallback.steps.slice()
        const target = index + direction
        if (target < 0 || target >= steps.length) return
        const tmp = steps[index]
        steps[index] = steps[target]
        steps[target] = tmp
        this.$set(this.new_fallback, 'steps', steps)
      },
      removeFallbackChain(lang) {
        if (!this.settings_form.fallback_langs) return
        this.$delete(this.settings_form.fallback_langs, lang)
      },
      queueStaticTermSearch() {
        clearTimeout(this._static_term_search_timeout)
        if (!this.static_term_search.trim()) {
          this.static_term_results = []
          this.static_term_searching = false
          return
        }
        this._static_term_search_timeout = setTimeout(() => this.searchStaticTerms(), 250)
      },
      searchStaticTerms() {
        const q = this.static_term_search.trim()
        if (!q) {
          this.static_term_results = []
          return
        }
        this.static_term_searching = true
        axios.post('?op-api=search-categories', {}, { params: { q } })
          .then(res => {
            const selected = new Set(this.settings_form.static_terms || [])
            this.static_term_results = (res.data || []).filter(cat => !selected.has(cat.id))
          })
          .finally(() => {
            this.static_term_searching = false
          })
      },
      addStaticTerm(cat) {
        if (!cat || !cat.id) return
        if (!this.settings_form.static_terms) {
          this.$set(this.settings_form, 'static_terms', [])
        }
        if (this.settings_form.static_terms.includes(cat.id)) return
        this.settings_form.static_terms.push(cat.id)
        this.$set(this.static_term_labels, cat.id, cat)
        this.static_term_search = ''
        this.static_term_results = []
      },
      removeStaticTerm(id) {
        if (!this.settings_form.static_terms) return
        const idx = this.settings_form.static_terms.indexOf(id)
        if (idx === -1) return
        this.settings_form.static_terms.splice(idx, 1)
        this.$delete(this.static_term_labels, id)
      },
      syncStaticTermLabels() {
        if (!this.server_config || !this.server_config.static_term_labels) return
        this.static_term_labels = { ...this.server_config.static_term_labels }
      },
      sortedResources(resources) {
        return _.sortBy(Object.values(resources || {}), 'label')
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
            this.getServerConfig()
          })
          .finally(res => {
            this.is_importing = false
          })
      },
      refreshNextSchema() {
        this.is_loading_next_schema = true
        axios.post('?op-api=next-schema').then(res => {
            this.next_schema = res.data
            this.syncResourceTypesFromSchema()
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
              let m = files.find(x => x.info.token === token)
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
          this.syncResourceTypesFromSchema()
          this.syncStaticTermLabels()
        })
      },
      fieldById(id) {
        for (const r of this.next_schema.resources)
          for (const f of r.fields)
            if (f.id === id) return f
      },
      relatedFieldResource(id) {
        const f = this.fieldById(id)
        if (!f) return
        for (const r of this.next_schema.resources)
          if (r.id === f.rel_res_id) return r
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
      'new_fallback.lang'(lang, prev) {
        if (lang !== prev) {
          this.new_fallback.steps = []
        }
      },
      'new_locale_mapping.locale'(locale) {
        this.locale_mapping_error = ''
        if (!locale) {
          this.new_locale_mapping.lang = ''
          return
        }
        const w = this.wpmlLanguageByLocale(locale)
        if (!w) return
        const guess = this.guessOnpageLangForWpml(w)
        if (guess) this.new_locale_mapping.lang = guess
      },
    },
  })
</script>
