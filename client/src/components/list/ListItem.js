import React from 'react';
import Moment from 'react-moment';
import { Link } from 'react-router-dom';
import DefaultArticleImg from '../../assets/images/smartphone-screen-with-art-photo-gallery-application-3850271-mid.jpg';
import '../article/ArticleItem.css';

const ListItem = ({ 
    id,
    created_at,
    title, 
    commentsTotal, 
    itemsTotal,
    viewsTotal, 
    likesTotal, 
    downloadsTotal,
    hashtags
}) => (
    <div className="col-lg-4 col-md-6 col-sm-8">
        <div className="card mb-4 shadow-sm">
            <Link to={'/list/' + id}>
            <img src={DefaultArticleImg}
                alt="article-image"
                height="225" width="100%"
                className="timelines-image hover"     
            />
            </Link>
            <div className="card-body">
                <Link to={'/list/' + id} className="article-title-link">
                    <h4 className="card-text article-title"> {title}</h4> 
                </Link>
                <p> {hashtags.map(tag => <Link key={tag.id} className="tag-link" to="/">{tag.content} </Link>)} </p>
                <p className="text-muted">
                    <Moment className="text-muted" format="Do MMM YYYY">
                        {created_at}
                    </Moment>
                </p>
                <p className="text-muted">
                    {itemsTotal} items &nbsp;
                    {commentsTotal} comments&nbsp;
                    {likesTotal} likes
                </p>
                <hr/>
                <div className="d-flex justify-content-between align-items-center text-muted">
                        <span>
                         {commentsTotal}&nbsp;N1 
                        </span>
                        <span>
                         {viewsTotal}&nbsp;N2
                        </span>
                        <span>
                         {likesTotal}&nbsp;N3
                        </span>
                        <span>
                         {downloadsTotal}&nbsp;N4
                        </span>
                        <span>
                         {downloadsTotal}&nbsp;N5
                        </span>
                </div>
                
            </div>
        </div>
    </div>
);

export default ListItem;