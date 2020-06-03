import React from 'react';
// import Moment from 'react-moment';
import { Link } from 'react-router-dom';
import Moment from 'react-moment';


const PostItem = ({ 
    id,
    title,
    content,
    type,
    locked,
    userId,
    commentsTotal,
    likesTotal,
    viewsTotal,
    downloadsTotal,
    hashtags,
    user,
    postType,
    date
}) => (
    <div className="row">
        <div className="col-10 pb-3 pt-3 border-bottom border-gray">
            <p>
                <strong className="d-block text-gray-dark">{user.name}</strong>
                <Link to={`/community/${id}`}>
                    {title}
                </Link>
            </p>
            Date: <Moment className="text-muted" format="Do MMM YYYY"> 
                {date} 
            </Moment>
            <br/>
            Tags: {hashtags.map(tag => <span key={tag.id} className="tag-link">{tag.content} </span>)}
        </div>
        <div className="col-2">
            <div className="">
                <span>
                    {commentsTotal}&nbsp;Comments 
                </span> <br/>
                <span>
                    {viewsTotal}&nbsp;Views
                </span> <br/>
                <span>
                    {likesTotal}&nbsp;Likes &nbsp;
                </span> <br/>
                <span>
                        <strong className="d-block text-gray-dark">{postType}</strong>
                </span>
            </div>
        </div>
    </div>
);

export default PostItem;