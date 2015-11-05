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
            appInstance.on('appResponses', this._onAppResponses, this);

            var tp = this.template,
                emlNode = tp.one('emailStatus');

            if (emlNode){
                emlNode.on('change', this._onEmailStatusChange, this);
            }

        },
        destructor: function(){
            this.get('appInstance').detach('appResponses', this._onAppResponses, this);
        },
        _onAppResponses: function(e){
            if (e.err || !e.result.subscribeSave){
                return;
            }
            this.renderSwitcher();
        },
        _onEmailStatusChange: function(e){
            if (this._disableEmlStEvent){
                return;
            }
            this.subscribeSave();
        },
        renderSwitcher: function(){
            var tp = this.template,
                subscribe = this.get('subscribe');

            if (!subscribe || !subscribe.get('owner')){
                return;
            }

            var owner = subscribe.get('owner'),
                sst = subscribe.get('status'),
                disable = !subscribe.isEnable();

            tp.each('buttonOn,buttonOff,emailStatus', function(node){
                node.set('disabled', disable);
            }, this);

            var srcNode = this.get('boundingBox');
            srcNode.all('.statusOn').each(function(node){
                if (sst === SST_ON){
                    node.removeClass('hide');
                } else {
                    node.addClass('hide');
                }
            });

            srcNode.all('.statusOff').each(function(node){
                if (sst === SST_OFF){
                    node.removeClass('hide');
                } else {
                    node.addClass('hide');
                }
            });

            this._disableEmlStEvent = true;
            tp.setValue('emailStatus', subscribe.get('emailStatus'));
            this._disableEmlStEvent = false;
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
            var tp = this.template,
                emlStNode = tp.one('emailStatus'),
                subscribe = this.get('subscribe'),
                owner = subscribe.get('owner');

            if (emlStNode){
                subscribe.set('emailStatus', tp.getValue('emailStatus'));
            }

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

    NS.SubscribeConfigButtonWidget = Y.Base.create('subscribeConfigButtonWidget', SYS.AppWidget, [
        NS.SwitcherStatusExt
    ], {}, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'configButton'}
        }
    });
};