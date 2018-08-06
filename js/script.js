var image = new Image();
image.src = '/img/loading.gif';

var i;

function Show (showName, showID, episodes, nextEpNum) {
    var thisObj = this;
    
    thisObj.showName = showName;
    thisObj.showID = showID;
    thisObj.episodes = episodes;
    thisObj.nextEpNum = nextEpNum;
    
    thisObj.showEl = document.getElementById('show-'+showID);
    thisObj.remainingEpsEl = document.getElementById('show-'+showID+'-remaining-episodes');
    thisObj.nextEpNameEl = document.getElementById('show-'+showID+'-next-ep-name');
    thisObj.positionEl = document.getElementById('show-'+showID+'-position');
    thisObj.positionSlashEl = document.getElementById('show-'+showID+'-position-slash');
    thisObj.durationEl = document.getElementById('show-'+showID+'-duration');
    thisObj.toggleMoreCheckbox = document.getElementById('show-'+showID+'-toggle-more');
    thisObj.prevBtn = document.getElementById('show-'+showID+'-prev');
    thisObj.randBtn = document.getElementById('show-'+showID+'-rand');
    thisObj.nextBtn = document.getElementById('show-'+showID+'-next');
    thisObj.firstBtn = document.getElementById('show-'+showID+'-first');
    thisObj.endBtn = document.getElementById('show-'+showID+'-end');
    
    thisObj.scrollToEp(false);
    
    // Play episode on click
    thisObj.showEl.addEventListener('click', function(e) {
        if (findAncestorByClass(e.target, ['more-info', 'toggle-more-info-container'], 'show') === false) thisObj.playNextEp();
    });
    
    // Toggle more info from checkbox
    thisObj.toggleMoreCheckbox.checked = false;
    thisObj.toggleMoreCheckbox.addEventListener('change', function() {
        thisObj.showEl.parentNode.classList.toggle('expanded', this.checked);
    });
    
    // Episode Navigation
    thisObj.prevBtn.addEventListener('click', function() {
        thisObj.epNav('prev');
    });
    thisObj.randBtn.addEventListener('click', function() {
        thisObj.epNav('rand');
    });
    thisObj.nextBtn.addEventListener('click', function() {
        thisObj.epNav('next');
    });
    thisObj.firstBtn.addEventListener('click', function() {
        thisObj.epNav('first');
    });
    thisObj.endBtn.addEventListener('click', function() {
        thisObj.epNav('end');
    });
    
    var links = thisObj.showEl.getElementsByClassName('ep-list-nav'),
        linkCount = links.length,
        link, epNum;
    for (var i=0; i<linkCount; ++i)  {
        link = links[i];
        link.addEventListener('click', function() {
            epNum = parseInt(this.dataset.epnum);
            thisObj.epNav(epNum);
        });
    }
    
    // Keyboard Navigation
    thisObj.showEl.addEventListener('keydown', function(e) {
        var key = e.keyCode;
        switch (key) {
            case 32:
                e.preventDefault();
            case 13:
                thisObj.playNextEp();
                break;
            case 37:
                thisObj.epNav('prev');
                break;
            case 39:
                thisObj.epNav('next');
                break;
            case 82:
                thisObj.epNav('rand');
                break;
            case 40:
                if (!thisObj.toggleMoreCheckbox.checked) {
                    e.preventDefault();
                    thisObj.toggleMoreCheckbox.click();
                }
                break;
            case 38:
                if (thisObj.toggleMoreCheckbox.checked) {
                    e.preventDefault();
                    thisObj.toggleMoreCheckbox.click();
                }
                break;
        }
    });
}
Show.prototype.playNextEp = function() {
    if (this.nextEpNum < this.episodes.length) {
        // Update db
        var data = createPostDataObj({showID: this.showID});
        ajax('scripts/update_last_watched_time.php', 'POST', data);
        playFile(stripslashes(this.episodes[this.nextEpNum].pathname));
    }
};
Show.prototype.getLastEpId = function() {
    if (this.nextEpNum > 0) return this.episodes[this.nextEpNum - 1].id;
    else return 0;
};
Show.prototype.setNextEp = function(nextEpNum) {
    this.nextEpNum = nextEpNum;
    this.showEl.setAttribute('data-nextepnum', nextEpNum);
    // Update db
    var data = createPostDataObj({showID: this.showID, epID: this.getLastEpId()});
    ajax('scripts/update_last_eps.php', 'POST', data);
    // Update html
    var nextEp = this.episodes[nextEpNum];
    if (nextEpNum < this.episodes.length) {
        this.remainingEpsEl.innerHTML = this.episodes.length - nextEpNum;
        this.nextEpNameEl.innerHTML = nextEp.epName;
        this.nextEpNameEl.setAttribute('title', nextEp.epName);
        this.positionEl.innerHTML = nextEp.position;
        this.positionSlashEl.innerHTML = '/';
        this.durationEl.innerHTML = nextEp.duration;
        this.nextBtn.removeAttribute('disabled');
        this.endBtn.removeAttribute('disabled');
        this.showEl.parentNode.classList.add('active');
    } else {
        this.remainingEpsEl.innerHTML = 0;
        this.nextEpNameEl.innerHTML = '';
        this.nextEpNameEl.setAttribute('title', '');
        this.positionEl.innerHTML = '';
        this.positionSlashEl.innerHTML = '';
        this.durationEl.innerHTML = '';
        this.nextBtn.setAttribute('disabled', 'true');
        this.endBtn.setAttribute('disabled', 'true');
        this.showEl.parentNode.classList.remove('active');
    }
    if (nextEpNum == 0) {
        this.firstBtn.setAttribute('disabled', 'true');
        this.prevBtn.setAttribute('disabled', 'true');
    } else {
        this.firstBtn.removeAttribute('disabled');
        this.prevBtn.removeAttribute('disabled');
    }
    this.scrollToEp(true);
};
Show.prototype.epNav = function(action) {
    if (action === 'prev') {
        if (this.nextEpNum > 0) this.setNextEp(this.nextEpNum - 1);
    } else if (action === 'next') {
        if (this.nextEpNum < this.episodes.length)  this.setNextEp(this.nextEpNum + 1);
    } else if (action === 'rand') {
        var randEpNum = this.nextEpNum;
        while (randEpNum == this.nextEpNum) {
            randEpNum = getRandomIntInclusive(0, this.episodes.length - 1);
        }
        this.setNextEp(randEpNum);
    } else if (action === 'first') {
        this.setNextEp(0);
    } else if (action === 'end') {
        this.setNextEp(this.episodes.length);
    } else if (!isNaN(action) && parseInt(action) >= 0) {
        this.setNextEp(parseInt(action));
    }
};
Show.prototype.scrollToEp = function(animated) {
    var epList = this.showEl.getElementsByClassName('episode-list')[0],
        scrollToEpNum = this.nextEpNum,
        duration = (animated ? 200 : 0);
    if (scrollToEpNum > this.episodes.length - 1) scrollToEpNum = this.episodes.length - 1
    var scrollToEp = document.getElementById('show-'+this.showID+'-ep-nav-'+scrollToEpNum),
        scrollToEpTop = scrollToEp.offsetTop,
        scrollToEpH = scrollToEp.offsetHeight,
        scrollToEpBottom = scrollToEpTop + scrollToEpH,
        epListH = epList.offsetHeight,
        epListTop = epList.scrollTop,
        epListBottom = epListTop + epListH,
        buffer = 50;
    if (scrollToEpBottom + buffer > epListBottom) cstmScrollTo(epList, scrollToEpBottom + buffer - epListH, duration);
    else if (scrollToEpTop - buffer < epListTop) cstmScrollTo(epList, scrollToEpTop - buffer, duration);
}

// Scan directories by request
var scanDirBtn = document.getElementById('scan-directory-btn');
if (scanDirBtn) scanDirBtn.addEventListener('click', scanShowDir);

var shows = [];
afterShowsLoaded();

// Esc closes all more-info dropdowns
document.addEventListener('keydown', function(e) {
    if (e.keyCode == 27) closeMoreInfos(false);
});

document.addEventListener('click', function(e) {
    var showAncestor = findAncestorByClass(e.target, ['show'], false);
    closeMoreInfos(showAncestor);
});

function afterShowsLoaded() {
    shows = [];
    var thisShowData, show;
    for (i=0; i<showData.length; ++i) {
        thisShowData = showData[i];
        show = new Show(thisShowData.showName, thisShowData.id, thisShowData.episodes, thisShowData.nextEpNum);
        shows.push(show);
    }
    showData = false; // Save memory; probably unnecessary
    
    // Focus on first show
    if (shows.length) document.getElementsByClassName('show').item(0).focus();
}

function scanShowDir(responseText, loadingBtn) {
    toggleLoading(scanDirBtn, true);
    ajax('/scripts/scan_show_dir.php', 'GET', null, handleShowDirScan, scanDirBtn);
}

function handleShowDirScan(xhrResponse, loadingBtn) {
    toggleLoading(loadingBtn, false);
    try {
        responseObj = JSON.parse(xhrResponse);
    } catch (e) {
        alert('Could not parse ajax response. Data returned:\n\n' + xhrResponse);
        return false;
    }
    if (responseObj.hasHtml) {
        document.getElementById('container').innerHTML = responseObj.html;
        addFormListeners();
    } else {
        location.reload();
    }
}

function addFormListeners() {
    var openFolderLinks = document.getElementsByClassName('open-folder');
    for (i=0; i<openFolderLinks.length; ++i) {
        openFolderLinks[i].addEventListener('click', function() {
            openFolder(this.dataset.path);
        });
    }
    
    var openFileLinks = document.getElementsByClassName('open-file');
    for (i=0; i<openFileLinks.length; ++i) {
        openFileLinks[i].addEventListener('click', function() {
            playFile(this.dataset.pathname);
        });
    }
    
    var forms = document.getElementsByTagName('form');
    for (i=0; i<forms.length; ++i) {
        forms[i].addEventListener('submit', function(e) {
            e.preventDefault();
            var submitBtn = this.getElementsByClassName('submit-btn').item(0);
            toggleLoading(submitBtn, true);
            var formData = new FormData(this),
                url = this.action;
            ajax(url, 'POST', formData, scanShowDir, submitBtn);
            return false;
        });
    }
}

function openFolder(path) {
    var data = createPostDataObj({path: path});
    ajax('scripts/open_folder.php', 'POST', data);
}

function playFile(pathname) {
    var data = createPostDataObj({pathname: pathname});
    ajax('scripts/play_file.php', 'POST', data);
}

function toggleLoading(el, isLoading) {
    if (isLoading) {
        el.setAttribute('disabled', 'true');
        el.classList.add('loading');
    } else {
        el.removeAttribute('disabled');
        el.classList.remove('loading');
    }
}

function ajax(url, method, data, successCallback, loadingBtn) {
    var xhr;
    if (window.XMLHttpRequest) xhr = new XMLHttpRequest();
    else xhr = new ActiveXObject("Microsoft.XMLHTTP");
    xhr.open(method, url, true);
    xhr.onreadystatechange = function() {
        if (xhr.readyState >= 4) {
            if (xhr.status === 200) {
                if (successCallback) successCallback(xhr.responseText, loadingBtn);
            }
            else {
                if (loadingBtn) toggleLoading(loadingBtn, false);
                alert('ajax error! status = ' + xhr.status);
            }
        }
    }
    var xhrBody = '';
    if (method === 'POST') xhrBody = data;
    xhr.send(xhrBody);
}

function closeMoreInfos(exceptionShowEl) {
    for (i=0; i<shows.length; ++i) {
        show = shows[i];
        if (exceptionShowEl !== show.showEl && show.toggleMoreCheckbox.checked) show.toggleMoreCheckbox.click();
    }
}

function stripslashes(str) {
    str = str.replace(/\\'/g, '\'');
    str = str.replace(/\\"/g, '"');
    str = str.replace(/\\0/g, '\0');
    str = str.replace(/\\\\/g, '\\');
    return str;
}

function getRandomIntInclusive(min, max) {
    min = Math.ceil(min);
    max = Math.floor(max);
    return Math.floor(Math.random() * (max - min + 1)) + min;
}

function findAncestorByClass(el, classes, stopClass) {
    var parent = el.parentNode;
    if (parent === document) return false;
    if (stopClass && hasClass(parent, stopClass)) return false;
    for (var i=0; i<classes.length; ++i) {
        if (hasClass(parent, classes[i])) return parent;
    }
    return findAncestorByClass(parent, classes, stopClass);
}

function hasClass(el, className) {
    return (' ' + el.className + ' ').indexOf(' ' + className + ' ') > -1;
}

function createPostDataObj(dataArr) {
    var formData = new FormData();
    for (var prop in dataArr) {
        if(!dataArr.hasOwnProperty(prop)) continue;
        formData.append(prop, dataArr[prop]);
    }
    return formData;
}

function cstmScrollTo(element, to, duration) {
    if (duration == 0) {
        element.scrollTop = to;
        return true;
    }
    var start = element.scrollTop,
        change = to - start,
        startDate = +new Date(),
        // t = current time
        // b = start value
        // c = change in value
        // d = duration (ms)
        easeInOutQuad = function(t, b, c, d) {
            t /= d/2;
            if (t < 1) return c/2*t*t + b;
            t--;
            return -c/2 * (t*(t-2) - 1) + b;
        },
        animateScroll = function() {
            var currentDate = +new Date(),
                currentTime = currentDate - startDate;
            element.scrollTop = parseInt(easeInOutQuad(currentTime, start, change, duration));
            if (currentTime < duration) requestAnimationFrame(animateScroll);
            else element.scrollTop = to;
        };
    animateScroll();
};