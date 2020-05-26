import React from 'react'

const Searchbar = () => {
    return (
        <div>
            <form class="form-inline d-flex justify-content-center md-form form-sm active-cyan-2 mt-2">
            <input class="form-control form-control-sm mr-3 w-75" type="text" placeholder="Search"
                aria-label="Search"/>
            <i class="fas fa-search" aria-hidden="true"></i>
            </form>
        </div>
    )
}

export default Searchbar;




