const http=require('http');
const express=require('express');
const axios = require('axios');
const cheerio = require('cheerio')
const crypto = require('crypto')

const app=express();
  
class ItemSearch{
    constructor(shop_disp, keyword, sort, tr_keyword){
        this.item_id=0;
        this.image="";
        this.url="";
        this.title="";
        this.price="";
        this.shop="";    
        this.items=[];

        this.shop_disp=shop_disp;
        this.keyword=keyword;
        this.sort=sort;
        this.tr_keyword=tr_keyword;
    }

    amazon=()=>{
        return new Promise((resolve,reject)=>{
            //console.log('amazon method');
            const sort_str={'relevanceblender':'relevanceblender',
                        'review-rank':'review-rank',
                        'price-asc-rank':'price-asc-rank',
                        'price-desc-rank':'price-desc-rank'}; // 各ショップでのsortの名称 おすすめ（relevanceblender）でなくreview-rankにする
            const hits_set = 10; // 取得件数（商品数）
            const get_url="https://www.amazon.co.jp/s?k=" 
                + encodeURI(this.keyword)
                +"&s="+encodeURI(sort_str[this.sort]);

            axios.get(get_url)
            .then(response => {
                const htmlParcer=response.data;
                //console.log(htmlParcer);
                const $ = cheerio.load(htmlParcer);
                $('.a-spacing-base').each((item_num,node) => {
                    //console.log('item_num>=hits_set');
                    if(item_num>=hits_set){ // hits_set分アイテムを追加したら、それ以降は処理を終了
                        return false;
                    }
                    this.shop='amazon';
                    this.image =$(node).find('img').attr('src');
                    const oringin_url = 'https://www.amazon.co.jp'+$(node).find('.a-link-normal').attr('href');
                    this.title=$(node).find("span.a-text-normal").text();
                    this.price=$(node).find('.a-price-whole').text().replace('￥','').replace(',','');
                    this.item_id=this.md5hex(this.image); // 画像URLでitem_idを生成する
                    if (this.price=="" | this.item_id=="") return false; // 価格またはidがないものは飛ばす
                    this.url =oringin_url+'&tag=amazonsearch-22';

                    //console.log('item_num: ',item_num);
                    //console.log('image: ',this.image);
                    //console.log('oringin_url: ',oringin_url);
                    //console.log('cheerio title: ',this.title);
                    //console.log('price: ',this.price);
                    //console.log('item_id: ',this.item_id);
                    //console.log('url: ',this.url);

                    (async ()=>{
                        await this.add_item();
                    })().catch(error => {console.log(error,"item_num: ",item_num);});
                });
                //resolve(htmlParcer);
                resolve(this.items);
            })
            .catch(error => {
                console.log(error);
                reject(error);
            })
        });
    };
    md5hex=(str)=>{
        const md5 = crypto.createHash('md5')
        return md5.update(str, 'binary').digest('hex')
    };
    add_item=()=>{
        return new Promise((resolve,reject)=>{
            if (!this.items.some(item => item.item_id === this.item_id)){
                this.items.push(
                    {'item_id':this.item_id,'image':this.image,'url':this.url,'title':this.title,'price':this.price,'shop':this.shop}
                )
                resolve(this.items);
            }
            else{
                //console.log('some: ',this.items.some(item => item.item_id === this.item_id));
                //console.log("item_id:",this.item_id);
                //console.log("title:",this.title);
                //console.log("this.items: ",this.items);
                //reject("add_item error, item_id:",this.item_id);
                reject("add_item error");
            }
        });
    };
}

/*
const is_log=new ItemSearch('amazon','プログラミング','relevanceblender','');
(async ()=>{
    await is_log.amazon();
    console.log("items: ",is_log.items);
})().catch(error => {console.log(error);});
*/
app.get("/", (req,res,next)=>{
    (async ()=>{
        const is=new ItemSearch(
            req.query.shop, req.query.keyword, req.query.order, req.query.tr_keyword
        );
        const result=await is.amazon();
        return res.json(result);
    })().catch(next);
});

//const server=http.createServer(app);
//server.listen(3000);

app.listen(3000);
