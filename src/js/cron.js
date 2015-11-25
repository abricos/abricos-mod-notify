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

    var Cron = function(options, callback, context){
        NS.Cron.instance = this;
        var instance = this;
        NS.initApp({
            initCallback: function(err, appInstance){
                instance._onInitAppInstance(err, appInstance);
                callback.call(context || null);
            }
        });
    };
    Cron.instance = null;
    Cron.prototype = {
        _onInitAppInstance: function(err, appInstance){
            if (err){
                return;
            }
            this.appInstance = appInstance;
            this.cronStart();
        },
        _onAppResponses: function(e){
            if (e.err || !e.result.notifyList){
                return;
            }
            this.renderNotifyList();
        },
        cronStart: function(){
            var app = this.appInstance;
            if (app.cronIsStart()){
                return;
            }
            app.on('appResponses', this._onAppResponses, this);
            app.cronStart();
        },
        cronStop: function(){
            var app = this.appInstance;
            if (!app.cronIsStart()){
                return;
            }
            app.cronStop();
        }
    };
    NS.Cron = Cron;

    NS.initializeCron = function(options, callback, context){
        callback.call(context || null);

        if (NS.Cron.instance){
            callback.call(context || null);
        } else {
            new NS.Cron(options, function(){
                callback.call(context || null);
            });
        }

    };

};