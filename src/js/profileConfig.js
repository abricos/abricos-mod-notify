var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: '{C#MODNAME}', files: ['button.js']}
    ]
};
Component.entryPoint = function(NS){

    var Y = Brick.YUI,
        COMPONENT = this,
        SYS = Brick.mod.sys;

    var SST_ON = NS.Subscribe.STATUS_ON,
        SST_OFF = NS.Subscribe.STATUS_OFF;

    NS.ProfileConfigWidget = Y.Base.create('profileConfigWidget', SYS.AppWidget, [], {
        onInitAppWidget: function(err, appInstance){
            var tp = this.template;

            this.rootButton = new NS.RootSubscribeConfigButtonWidget({
                srcNode: tp.one('rootButton')
            });

            this._widgets = [];

            this.set('waiting', true);
            this._initModuleWidgets(function(){
                this.set('waiting', false);
            });
        },
        destructor: function(){
            var ws = this._widgets || [];
            for (var i = 0; i < ws.length; i++){
                ws[i].destroy();
                ws[i] = null;
            }
        },
        _initModuleWidgets: function(callback){
            var ownerList = this.get('appInstance').get('ownerList'),
                instance = this,
                modDistinct = {},
                modules = [];

            ownerList.each(function(owner){
                var module = owner.get('module');
                if (module !== ''){
                    modDistinct[module] = true;
                }
            }, this);

            for (var module in modDistinct){
                modules[modules.length] = module;
            }

            var initApp = function(stack){
                if (stack.length === 0){
                    return callback.call(instance);
                }
                var module = stack.pop();

                Brick.use(module, 'subscribeConfig', function(err, ns){
                    if (err || !ns['SubscribeConfigWidget']){
                        return initApp(stack);
                    }
                    var Widget = ns['SubscribeConfigWidget'];
                    instance._initModuleWidget(Widget);
                    initApp(stack);
                });
            };

            initApp(modules);
        },
        _initModuleWidget: function(Widget){
            var tp = this.template,
                ws = this._widgets;

            ws[ws.length] = new Widget({
                srcNode: tp.append('list', '<div></div>')
            });
        }
    }, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'widget'}
        },
        CLICKS: {}
    });


    NS.RootSubscribeConfigButtonWidget = Y.Base.create('rootSubscribeButtonWidget', SYS.AppWidget, [
        NS.SwitcherStatusExt
    ], {}, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'rootButton'},
            ownerDefine: {value: {}}
        }
    });


};