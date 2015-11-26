var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: 'sys', files: ['appModel.js']}
    ]
};
Component.entryPoint = function(NS){
    var Y = Brick.YUI,
        SYS = Brick.mod.sys;

    NS.Owner = Y.Base.create('owner', SYS.AppModel, [], {
        structureName: 'Owner',
        isEnable: function(){
            var parent = this, val;
            while (parent){
                val = parent.get('status') === NS.Owner.STATUS_ON;
                if (!val){
                    break;
                }
                parent = parent.get('parent');
            }
            return val;
        }
    }, {
        STATUS_ON: 'on',
        STATUS_OFF: 'off',
        ATTRS: {
            parent: {
                readOnly: true,
                getter: function(val){
                    if (Y.Lang.isUndefined(val)){
                        var parentid = this.get('parentid');
                        if (parentid === 0){
                            val = null;
                        } else {
                            val = this.appInstance.get('ownerBaseList').getById(parentid);
                        }
                    }
                    return val;
                }
            },
            key: {
                readOnly: true,
                getter: function(val){
                    if (val){
                        return val;
                    }

                    var a = [
                        this.get('module'), this.get('type'),
                        this.get('method'), this.get('itemid')
                    ];
                    val = a.join(':');

                    return val;
                }
            }
        },
        normalizeKey: function(key, itemid){
            if (!Y.Lang.isString(key)){
                key = '';
            }
            itemid = itemid | 0;
            key = key.replace('{v#itemid}', itemid);

            var a = [], aa = key.split(':');
            for (var i = 0; i < 4; i++){
                a[a.length] = i < 3 ? aa[i] || '' : aa[i] | 0;
            }
            return a.join(':');
        }
    });

    NS.OwnerList = Y.Base.create('ownerList', SYS.AppModelList, [], {
        appItem: NS.Owner,
        getByKey: function(key){
            var ret = null;
            this.some(function(owner){
                if (key === owner.get('key')){
                    ret = owner;
                    return true;
                }
            }, this);
            return ret;
        }
    });

    NS.Subscribe = Y.Base.create('subscribe', SYS.AppModel, [], {
        structureName: 'Subscribe',
        isEnable: function(){
            if (!this.get('owner').isEnable()){
                return false;
            }
            var parent = this.get('parent'), val = true;
            while (parent){
                val = parent.get('status') === NS.Subscribe.STATUS_ON;
                if (!val){
                    break;
                }
                parent = parent.get('parent');
            }
            return val;
        }
    }, {
        STATUS_ON: 'on',
        STATUS_OFF: 'off',
        ATTRS: {
            owner: {
                readOnly: true,
                getter: function(val){
                    if (Y.Lang.isUndefined(val)){
                        var ownerid = this.get('ownerid');
                        val = this.appInstance.get('ownerList').getById(ownerid);
                    }
                    return val;
                }
            },
            parent: {
                readOnly: true,
                getter: function(val){
                    if (!Y.Lang.isUndefined(val)){
                        return val;
                    }
                    var parentid = this.get('parentid');
                    return this.appInstance.get('subscribeList').getById(parentid);
                }
            }
        }
    });

    NS.SubscribeList = Y.Base.create('subscribeList', SYS.AppModelList, [], {
        appItem: NS.Subscribe,
        getByKey: function(key){
            var app = this.appInstance,
                ownerList = app.get('ownerList'),
                owner = ownerList.getByKey(key);

            if (!owner){
                return null;
            }
            return this.getBy('ownerid', owner.get('id'));
        }
    });

    NS.Summary = Y.Base.create('summary', SYS.AppModel, [], {
        structureName: 'Summary',
    }, {});

    NS.SummaryList = Y.Base.create('summaryList', SYS.AppModelList, [], {
        appItem: NS.Summary,
    });

    NS.Config = Y.Base.create('config', SYS.AppModel, [], {
        structureName: 'Config'
    });
};