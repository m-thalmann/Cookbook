import { ActivatedRoute } from '@angular/router';

const RouteHelper = {
  getRouteLeaf: (route: ActivatedRoute | null) => {
    let leaf = route;

    while (leaf?.firstChild) {
      leaf = leaf.firstChild;
    }

    return leaf;
  },
};

export default RouteHelper;
