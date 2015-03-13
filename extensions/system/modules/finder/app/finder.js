(function ($, window) {

    var defaults = {
        root    : '/',
        mode    : 'write',
        view    : 'table',
        path    : '/',
        selected: [],
        upload  : {}
    };

    Vue.component('v-finder', {

        replace : true,
        template: '#finder.main',
        resource: null,

        data: function() {
            return Vue.util.extend({}, defaults);
        },

        ready: function () {

            var self = this;

            System.template('finder.thumbnail');

            this.resource = this.$resource('system/finder/:cmd');

            this.$watch('path', this.loadPath);

            this.loadPath();
            this._initUpload();

            this.$watch('selected', function(selected, old) {
                if (selected != old) self.$dispatch('select.finder', self.getSelected(), self)
            });

            this.$dispatch('ready.finder', this);
        },

        filters: {

            searched: function (files) {
                var query = this.search;
                return query ? files.filter(function (file) {
                    return file.name.toLowerCase().indexOf(query.toLowerCase()) !== -1;
                }) : files;
            }

        },

        computed: {

            breadcrumbs: function () {

                var path = '',
                    crumbs = [{ path: '/', title: this.$trans('Home') }]
                        .concat(this.path.substr(1).split('/')
                            .filter(function (str) {
                                return str.length;
                            })
                            .map(function (part) {
                                return { path: path += '/' + part, title: part };
                            })
                    );

                crumbs[crumbs.length - 1].current = true;

                return crumbs;
            }

        },

        methods: {

            encodeURI: function (url) {
                return encodeURI(url).replace(/'/g, '%27');
            },

            isWritable: function () {
                return this.mode === 'w' || this.mode === 'write';
            },

            isImage: function (url) {
                return url.match(/\.(?:gif|jpe?g|png|svg)/i);
            },

            loadPath: function (path) {

                var self = this;

                this.$set('selected', []);

                this.path = path || this.path;

                return this.resource.get({ path: this.path, root: this.root }, function (data) {

                    self.$set('folders', data.folders || []);
                    self.$set('files', data.files || []);

                    self.$dispatch('path.finder', self.getFullPath(), self)
                });
            },

            getFullPath: function() {
                return (this.root+this.path).replace(/^(\/)+|(\/)+$/g, '')+'/';
            },

            getSelected: function() {
                var path = this.getFullPath();
                return this.selected.map(function(name) {
                    return path+name;
                });
            },

            command: function (cmd, params) {

                var self = this;

                return this.resource.save({ cmd: cmd }, $.extend({ path: this.path, root: this.root }, params), function (data) {

                    UIkit.notify(data.message, data.error ? 'danger' : 'success');

                    self.loadPath();

                }).fail(function (jqXHR) {
                    UIkit.notify(jqXHR.status == 500 ? 'Unknown error.' : jqXHR.responseText, 'danger');
                });
            },

            createFolder: function () {
                var name = prompt(this.$trans('Folder Name'), '');

                if (!name) return;

                this.command('createfolder', { name: name });
            },

            rename: function (oldname) {
                var newname = prompt(this.$trans('New Name'), oldname);

                if (!newname || !oldname) return;

                this.command('rename', { oldname: oldname, newname: newname });
            },

            renameSelected: function () {
                var name = this.selected[0];
                if (name) this.rename(name);
            },

            remove: function (names) {
                if (!names || !confirm(this.$trans('Are you sure?'))) return;

                this.command('removefiles', { names: names });
            },

            removeSelected: function () {
                this.remove(this.selected);
            },

            _initUpload: function () {
                var self = this,
                    settings = {

                        action: this.$url('system/finder/upload'),

                        before: function (options) {
                            $.extend(options.params, { path: self.path, root: self.root });
                        },

                        loadstart: function () {
                            self.$set('upload.running', true);
                            self.$set('upload.progress', 0);
                        },

                        progress: function (percent) {
                            self.$set('upload.progress', Math.ceil(percent));
                        },

                        allcomplete: function (response) {

                            var data = $.parseJSON(response);

                            self.loadPath();

                            UIkit.notify(data.message, data.error ? 'danger' : 'success');

                            self.$set('upload.progress', 100);
                            setTimeout(function () {
                                self.$set('upload.running', false);
                            }, 1500);
                        }

                    };

                UIkit.uploadSelect(this.$el.querySelector('.uk-form-file > input'));
                UIkit.uploadDrop(this.$el, settings);
            }

        }

    });

    var Finder = function(element, options) {

        var deferred = $.Deferred();

        new Vue({
            el      : element,
            data    : $.extend(true, {}, defaults, options),
            template: '<div v-component="v-finder" v-with="$data"></div>',
            created: function() {
                this.$on('ready.finder', function(finder) {
                    deferred.resolve(finder);
                });
            }
        });

        return deferred;
    };

    $(function () {
        $('[data-finder]').each(function () {
            new Finder(this, $(this).data('finder'));
        });
    });

    window.Finder = window.Finder || Finder;

})(jQuery, window);