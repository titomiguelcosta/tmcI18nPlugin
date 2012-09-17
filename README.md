#tmcI18nPlugin#

symfony 1.4[1] plugin that adds MySQL support for internationalization instead of XLIFF files

## Installation ##

  * Install the plugin

        symfony plugin:install tmcI18nPlugin (deprecated)
    or
        git clone https://github.com/titomiguelcosta/tmcI18nPlugin.git plugins (recommended)

  * Activate the plugin in the `config/ProjectConfiguration.class.php` in case it isn't already active

        [php]
        class ProjectConfiguration extends sfProjectConfiguration
        {
          public function setup()
          {
            $this->enablePlugins(array(
              'sfDoctrinePlugin',
              'tmcI18nPlugin',
              '...'
            ));
          }
        }

  * Rebuild your model (in case you do not use migrations)

        symfony doctrine:build-model
        symfony doctrine:build-forms
        symfony doctrine:build-filters
        symfony doctrine:build-sql

  * Update you database tables by starting from scratch (it will delete all the existing tables, then re-creates them):

        symfony doctrine:insert-sql

    or do everything with one command

        symfony doctrine:build --all

  * Enable the modules in your `settings.yml` (optional)
    * For your backend application: transunit and transunit
    * *Note:* these modules make use of the plugin tmcTwitterBootstrapPlugin, if you do not want to install, feel free to generate new modules with the task doctrine:generate-admin

              all:
                .settings:
                  enabled_modules:      [default, catalogue, transunit, ...]

    * Check the address http://your_host/catalogue and http://your_host/translation

  * Edit factories.yml on the application config folder to use MySQL instead of XLIFF

              all:
                i18n:
                  param:
                    source:               tmcMySQL
                    database:             mysql://username:password@hostname/database
                    debug:                false
                    untranslated_prefix:  "[T]"
                    untranslated_suffix:  "[/T]"

  * Clear you cache

        php symfony cc

## Task ##

There is a task to extract the i18n strings. The task extends the native i18n:extract with an extra option named --catalogue, overcoming the limitation of xliff files, that only the messages catalogue is used. If it doesn't exist the refered catalogue, it is created.

   * Example:

        php symfony tmcI18n:extract --auto-save --catalogue=forms frontend pt

## Alert ##

- when adding a new catalgoue, name must specify the culture, for instance: messages.pt

- in case css files are not loaded, publish assets runnig the task $php symfony plugin:publish-assets

- don't forget to edit the dsn configuration for the database connection in the factories.yml

- don't forget to enable i18n in the settings.yml file of your config application folder
              all:
                .settings:
                  i18n: true

## ToDo ##

  * Offer the possibility to automatically translate with google translation

  * make it database independent

## Contact ##

  * Share your opinion and give feedback at symfony@titomiguelcosta.com
