import React, { Component } from 'react';
// import { connect } from 'react-redux';
// import { fetchLists } from '../store/actions/lists';
import { Link } from 'react-router-dom';
import ArrowIcon from '../assets/icons/arrow-navigation-icon.svg';
import { apiCall } from '../services/api';
import ExploreListItem from '../components/list/ExploreListItem';
import Spinner from '../assets/images/spinner.gif';

class ExploreCustomList extends Component {
    _isMounted = false;
    constructor(props) {
        super(props);
        this.state = {
            lists: [],
        }
    }

    componentDidMount() {
        this._isMounted = true;
        this.fetchLists();
    }

    componentWillUnmount() {
        this._isMounted = false;
      }

    fetchLists(){
        return apiCall("get", "/api/lists")
            .then(res => {
                if (this._isMounted) {
                    let newState = Object.assign({}, this.state);
                    newState.totalLists = res.lists.total;
                    newState.lists = [...newState.lists, ...res.lists.data];
                    this.setState( newState );
                }
            })
            .catch(err => {
                console.log(err);
            })
    };

    render() {
        const listTypes = [
            "knownradicals list",
            'knownkanjis list',
            'knownwords list',
            'knownsentences list',
            'Radicals List',
            'Kanjis List',
            'Words List',
            'Sentences List',
            'Articles List'
        ];

        let { lists } = this.props;

        let customLists = this.state.lists ? (this.state.lists.slice(0, 3).map(l => (
            <ExploreListItem
                key={l.id}
                id={l.id}
                type={l.type}
                listType={listTypes[l.type-1]}
                created_at={l.created_at}
                title={l.title}
                commentsTotal={l.commentsTotal}
                likesTotal={l.likesTotal}
                viewsTotal={l.viewsTotal}
                downloadsTotal={l.downloadsTotal}
                hashtags={l.hashtags.slice(0, 3)}
                itemsTotal={l.listItems.length}
                n1={l.n1}
                n2={l.n2}
                n3={l.n3}
                n4={l.n4}
                n5={l.n5}
            />
        )) ) : (
            <div className="container">
                <div className="row justify-content-center">
                    <img src={Spinner}/>
                </div>
            </div>
        )

        return (
            <React.Fragment>
               <Link to="/lists" className="homepage-section-title" id="lists">
                    <span>Lists ({this.state.totalLists})<img src={ArrowIcon} alt="arrow icon" /> </span>
                </Link>
                <div className="row">
                    {customLists}
                </div>
            </React.Fragment>
        );
    }
}

// function mapStateToProps(state) {
//     return {
//         lists: state.lists
//     };
// };

// export default connect(mapStateToProps, { fetchLists })(ExploreCustomList);
export default ExploreCustomList;