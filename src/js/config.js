var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: '{C#MODNAME}', files: ['lib.js']}
    ]
};
Component.entryPoint = function(NS){

    var Y = Brick.YUI,
        COMPONENT = this,
        SYS = Brick.mod.sys;

    NS.ConfigWidget = Y.Base.create('configWidget', SYS.AppWidget, [], {
        onInitAppWidget: function(err, appInstance, options){
            this.reloadConfig();
        },
        reloadConfig: function(){
            this.set('waiting', true);

            this.get('appInstance').config(function(err, result){
                this.set('waiting', false);
                this.renderConfig();
            }, this);
        },
        renderConfig: function(){
            var tp = this.template,
                config = this.get('appInstance').get('config'),
                attrs = config.toJSON(true);

            tp.toggleView(config.get('SMTP'), 'smtpUsePanel', 'smtpNotPanel');

            tp.setValue(attrs);
            tp.setValue({
                POPBefore: attrs.POPBefore ? 'true' : 'false',
                totestfile: attrs.totestfile ? 'true' : 'false'
            });
        },
        onSubmitFormAction: function(){
            this.set('waiting', true);

            var model = this.get('model'),
                instance = this;

            this.get('appInstance').configSave(model, function(err, result){
                instance.set('waiting', false);
                if (!err){
                    // instance.fire('editorSaved');
                }
            });
        },
    }, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'widget'},
        },
        CLICKS: {
            showConfigExample: {
                event: function(){
                    this.template.toggleView(true, 'configExample', 'showConfigExample');
                }
            }
        }
    });

};