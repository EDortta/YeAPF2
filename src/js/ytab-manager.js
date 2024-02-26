class yTabManager {
    constructor(containerId) {
        this.container = document.getElementById(containerId);
        var tabs = this.container.getElementsByClassName('tab');
        this.tabStack = [];
        this.currentTab = null;
        this.tabs = [];

        for (const tab of tabs) {
            if (tab.parentNode == this.container) {
                this.tabs.push(tab);
            }
        }

        for (const tab of this.tabs) {
            if (tab.dataset.default === 'true') {
                this.showTab(tab.id);
                this.currentTab = tab.id;
            } else {
                this.hideTab(tab.id);
            }
        }
    }

    hideTab(tabId) {
        for (const tab of this.tabs) {
            if (tab.id === tabId) {
                tab.style.display = 'none';
            }
        }
    }

    unhideTab(tabId) {
        for (const tab of this.tabs) {
            if (tab.id === tabId) {
                tab.style.display = 'block';
            } else {
                tab.style.display = 'none';
            }
        }
    }

    showTab(tabId) {
        this.unhideTab(tabId);
        this.tabStack.push(this.currentTab);
        this.currentTab = tabId;
    }

    closeTab() {
        const tabId = this.tabStack.pop();
        this.showTab(tabId);
    }
}