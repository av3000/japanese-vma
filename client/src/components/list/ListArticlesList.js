import React, { Component } from 'react';
import { Link } from 'react-router-dom';


class ListArticlesList extends Component {
    constructor(props){
        super(props);
        
    }

    render() {
        let { objects } = this.props;
        
        const objectList = objects.map(object => {
            object.hashtags = object.hashtags.slice(0, 3);
          return (
            <tr key={object.id}>
              <th scope="row">{ 
                <Link to={`/article/${object.id}`} target="_blank">
                    {/* {object.id}  */}
                    <i className="fas fa-external-link-alt"></i>
                </Link> }</th>
              <td>{object.viewsTotal}</td>
              <td>{object.savesTotal}</td>
              <td>{object.downloadsTotal}</td>
              <td>{object.commentsTotal}</td>
              <td>{object.likesTotal}</td>
              <td> {object.hashtags.map(tag => <Link key={tag.id} className="tag-link" to="/">{tag.content} </Link>)} </td>
            </tr>
          )
        })

        return (
          <table className="table table-responsive-md table-bordered table-hover">
            <thead>
              <tr>
                <th scope="col">#</th>
                <th scope="col">Views</th>
                <th scope="col">Saves</th>
                <th scope="col">Downloads</th>
                <th scope="col">Comments</th>
                <th scope="col">Likes</th>
                <th scope="col">Tags</th>
              </tr>
            </thead>
            <tbody>
              {objectList}
            </tbody>
          </table>
        )
    }
}

export default ListArticlesList;