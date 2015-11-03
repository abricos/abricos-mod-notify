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

    var SST_ON = NS.Subscribe.STATUS_ON,
        SST_OFF = NS.Subscribe.STATUS_OFF;

    var SwitcherStatusExt = function(){
    };
    SwitcherStatusExt.NAME = 'switcherStatusExt';
    SwitcherStatusExt.ATTRS = {
        notifyApp: {
            readOnly: true,
            getter: function(val){
                if (val){
                    return val;
                }
                var appInstance = this.get('appInstance');
                if (!appInstance){
                    return;
                } else if (appInstance.name === 'notifyApp'){
                    return appInstance;
                }

                return appInstance.getApp('notify');
            }
        },
        ownerItemId: {writeOnce: true},
        ownerDefine: {writeOnce: true},
        ownerKey: {
            readOnly: true,
            getter: function(val){
                if (Y.Lang.isString(val)){
                    return val;
                }
                var define = this.get('ownerDefine'),
                    itemid = this.get('ownerItemId');

                return NS.Owner.normalizeKey(define, itemid);
            }
        },
        owner: {
            readOnly: true,
            getter: function(val){
                if (val){
                    return val;
                }
                var app = this.get('notifyApp'),
                    ownerList = app.get('ownerList');

                return ownerList.getByKey(this.get('ownerKey'));
            }
        },
        subscribe: {
            getter: function(val){
                if (val){
                    return val;
                }
                var app = this.get('notifyApp'),
                    key = this.get('ownerKey');

                return app.get('subscribeList').getByKey(key);
            }
        }
    };
    SwitcherStatusExt.prototype = {
        onInitAppWidget: function(err, appInstance){
            this.renderSwitcher();
        },
        renderSwitcher: function(){
            var tp = this.template,
                subscribe = this.get('subscribe');

            if (!subscribe || !subscribe.get('owner')){
                tp.hide('buttonOn,buttonOff');
                return;
            }

            var owner = subscribe.get('owner'),
                sst = subscribe.get('status'),
                disable = !owner.isEnable();

            tp.each('buttonOn,buttonOff', function(node){
                node.set('disabled', disable);
            }, this);

            tp.toggleView(sst === SST_ON, 'buttonOff', 'buttonOn')
        },
        switchToOn: function(){
            this.get('subscribe').set('status', SST_ON);
            this.renderSwitcher();
            this.subscribeSave();
        },
        switchToOff: function(){
            this.get('subscribe').set('status', SST_OFF);
            this.renderSwitcher();
            this.subscribeSave();
        },
        subscribeSave: function(){
            var subscribe = this.get('subscribe'),
                owner = subscribe.get('owner');

            this.get('notifyApp').subscribeSave(owner.get('id'), subscribe.toJSON(true));
        },
        onClick: function(e){
            switch (e.dataClick) {
                case 'on':
                    this.switchToOn();
                    return true;
                case 'off':
                    this.switchToOff();
                    return true;
            }
        }
    };
    NS.SwitcherStatusExt = SwitcherStatusExt;

    NS.SubscribeRowButtonWidget = Y.Base.create('subscribeRowButtonWidget', SYS.AppWidget, [], {
        onInitAppWidget: function(err, appInstance){
            this.renderStatus();
        },
        destructor: function(){
        },
        renderStatus: function(){
            var tp = this.template,
                subscribe = this.get('subscribe'),
                owner = subscribe ? subscribe.get('owner') : null,
                disable = !subscribe || !owner || !owner.isEnable();

            tp.each('on,off,emailOn,emailOff,bosOn,bosOff', function(node){
                node.set('disabled', disable);
            }, this);

            if (!subscribe || !owner){
                tp.toggleView(true, 'on', 'buttons')
                return;
            }

            var sst = subscribe.get('status');

            tp.toggleView(sst === SST_ON, 'buttons,off', 'on')
        },
        switchToOn: function(){
            this.get('subscribe').set('status', SST_ON);
            this.renderStatus();
            this.subscribeSave();
        },
        switchToOff: function(){
            this.get('subscribe').set('status', SST_OFF);
            this.renderStatus();
            this.subscribeSave();
        },
        subscribeSave: function(){
            var subscribe = this.get('subscribe'),
                owner = subscribe.get('owner');

            this.get('appInstance').subscribeSave(owner.get('id'), subscribe.toJSON(true));
        },
    }, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'widget'}
        },
        CLICKS: {
            on: 'switchToOn',
            off: 'switchToOff'
        }
    });

};